# 03 — Domain / Multi-Tenancy

**AI Business Platform** · Estrategia de tenant, aislamiento (RLS) y custom domains

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: `01-Vision.md`, `02-Product-Requirements.md`
- **Estado**: Decisión fijada: **shared schema + Row-Level Security (RLS)**

---

## 1. Decisión de aislamiento

**Shared schema + Row-Level Security (RLS)** sobre una única instancia de PostgreSQL.

Justificación (2026):
- Costo fijo: una sola DB, un pool de conexiones, una migración, un backup.
- Escala: aguanta de cientos a miles de tenants con índices correctos.
- Seguridad por diseño: el filtro de tenant se aplica en la base de datos, no solo en la app.
- Elaislacionamento se complementa con roles de BD separados (la app no tiene BYPASSRLS).
- Si un cliente enterprise exige aislamiento físico, se evalúa schema-per-tenant como plan Enterprise (no en el MVP).

---

## 2. Jerarquía de actores y tenancy

```
PLATFORM (equipo FoxCode)
 ├─ Platform Admin ................. gestiona todo el sistema
 ├─ Agency User .................... configura tenants de clientes
 │
 └─ TENANT (empresa cliente)
     ├─ Tenant Owner ................ propietario, billing, roles
     ├─ Tenant Editor ............... contenido web + knowledge base
     ├─ Tenant Operator ............. bandeja de conversaciones, CRM
     └─ (Visitantes/Clientes) ....... interactúan vía canales
```

### Reglas de tenancy

- Toda fila de datos de negocio pertenece a **exactamente un** tenant.
- Los datos de plataforma (planes, modelos, catálogos globales) son **globales** y no llevan `tenant_id`.
- Los usuarios pueden pertenecer a múltiples tenants (ej.: agencia, dueño de varias empresas).

---

## 3. Implementación de RLS en PostgreSQL

### 3.1 Patrón base

```sql
-- 1. columna tenant_id en toda tabla de negocio
ALTER TABLE conversations ADD COLUMN tenant_id BIGINT REFERENCES tenants(id);

-- 2. política por tabla
ALTER TABLE conversations ENABLE ROW LEVEL SECURITY;

CREATE POLICY conversations_tenant_isolation
  ON conversations
  USING (tenant_id = current_setting('app.tenant_id')::bigint)
  WITH CHECK (tenant_id = current_setting('app.tenant_id')::bigint);

-- 3. índice compuesto con tenant_id al frente
CREATE INDEX idx_conversations_tenant_created
  ON conversations (tenant_id, created_at DESC);
```

### 3.2 Contexto por request

- En cada request (middleware) / job / comando / webhook se ejecuta:
  `set_config('app.tenant_id', <id>, true)` — el tercer argumento `true` lo hace local a la transacción, evitando fugas entre conexiones del pool.
- Para jobs asíncronos, el contexto se captura al encolar y se restaura al ejecutar.

### 3.3 Roles de base de datos

| Rol | Uso | Permisos |
|---|---|---|
| `app` | La aplicación | Lectura/escritura en datos de tenant; **sin BYPASSRLS** |
| `migrator` | Ejecutar migraciones | DDL; puede BYPASSRLS (solo en deploys) |
| `admin_platform` | Soporte/auditoría (controlado) | Acceso cruzado **restringido y auditado**, nunca por defecto |

### 3.4 Reglas duras

1. La app nunca usa un rol con BYPASSRLS.
2. Ninguna query de negocio se ejecuta sin contexto de tenant.
3. Las tablas globales se marcan como `RLS DISABLED` explícitamente y solo se acceden con reglas de autorización de la app.
4. **Test de fuga cross-tenant en CI desde el día 1**: intento de acceso cruzado que debe fallar.

---

## 4. Custom domains

### 4.1 Resolución

```
Request → Host header → Domain Resolver → Tenant → Website Config → Render
```

- `DomainResolver` (`app/Services/Site/DomainResolver.php`) normaliza el Host (minúsculas, sin `www.`, sin puerto) y consulta la tabla `domains` (independiente de RLS, es enrutamiento global) con `status = verified`. Normaliza `www.` tanto en el Host entrante como en el dominio guardado.
- Si no hay dominio verificado, intenta el subdominio de la plataforma (`slug.platform_domain` → `tenants.slug`).
- **Enrutamiento raíz (MVP1)**: `GET /` resuelve el Host; si hay tenant, sirve el sitio (`PublicSiteController`); si no, muestra la Landing. Con esto, un dominio personalizado o `slug.plataforma.test` renderiza el sitio sin prefijo de ruta.
- Si el Host no resuelve o el tenant está suspendido → 404 genérico (sin filtración de datos).

### 4.2 Flujo de verificación

1. El tenant ingresa su dominio (ej. `www.empresa.com`).
2. La plataforma muestra los registros DNS a crear (TXT de verificación y/o CNAME al dominio de la plataforma).
3. Se valida el TXT (proceso en cola) → se marca `verified_at`.
4. Cloudflare emite SSL automático para el dominio del cliente (DNS proxied).

### 4.3 Casos

| Caso | Comportamiento |
|---|---|
| Dominio verificado y activo | Render del sitio del tenant |
| Dominio sin verificar | Página de "agregar registro DNS" |
| Tenant suspendido / plan vencido | Página de aviso controlada |
| Dominio desconocido | 404 genérico (sin filtración de datos) |

### 4.4 Estado de implementación (MVP1)

- [x] `DomainResolver` (plataforma + custom domain con normalización `www.`).
- [x] Servir el sitio en `/` según Host (custom domain y subdominio plataforma).
- [x] UI de Domains (agregar, verificación TXT, principal, eliminar) y `DomainController`.
- [ ] Wildcard vhost local en Laragon para probar `*.pdi_saas.test`.
- [ ] Verificación TXT automática en cola (hoy marca verificado manualmente).

> **Probar custom domains en local (Laragon)**: añadir un vhost wildcard que apunte al proyecto para que `*.pdi_saas.test` resuelva (ej. config Apache `ServerAlias *.pdi_saas.test`, o apuntar `C:\Windows\System32\drivers\etc\hosts` + `VirtualHost` en Laragon). Sin esto, usar `/site/{slug}` como URL pública del tenant.

---

## 5. Roles y permisos por tenant

| Permiso | Owner | Editor | Operator |
|---|---|---|---|
| Gestionar plan/billing | ✅ | — | — |
| Invitar/gestionar usuarios | ✅ | — | — |
| Editar contenido web | ✅ | ✅ | — |
| Gestionar knowledge base | ✅ | ✅ | — |
| Ver bandeja de conversaciones | ✅ | — | ✅ |
| Gestionar contactos/leads | ✅ | — | ✅ |
| Configurar canales/integraciones | ✅ | — | — |
| Ver analytics | ✅ | ✅ | ✅ |

- RBAC por rol en la app; RLS es la red de seguridad inferior, no el control de rol.

---

## 6. Rutas de ejecución que nunca omiten tenant

1. **HTTP requests**: middleware central.
2. **Jobs/queues**: contexto capturado al encolar y restaurado al ejecutar.
3. **Webhooks entrantes**: el canal se asocia al tenant y se fija contexto antes de procesar.
4. **Comandos/scheduled tasks**: resuelven tenant(s) explícitamente.
5. **Exportaciones y scripts de datos**: mismo middleware de contexto; auditados.

---

## 7. Criterios de éxito

- [ ] Test de fuga cross-tenant en CI (unit + integration) que falla ante acceso cruzado.
- [ ] Un tenant no puede resolver ni leer el dominio/sitio de otro.
- [ ] Backups automáticos; restauración probada.
- [ ] Migración de esquema única (no por-tenant) mantiene el sistema operativo.
- [ ] Conteo de tenants, usuarios y documentos no afecta el rendimiento (índices por tenant_id).
