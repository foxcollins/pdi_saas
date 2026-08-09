# 10 — Implementation

**AI Business Platform** · Plan de ejecución técnica

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: `04-Architecture.md`, `05-Data-Model.md`, `09-MVP-Backlog.md`
- **Estado**: Guía de arranque e implementación

---

## 1. Preparación del entorno local

### 1.1 Requisitos (Laragon + Docker)

- Laragon: PHP 8.2+ (actualmente 8.2.17), Composer 2.5, Node 22, **PostgreSQL 16 corriendo** (puerto 5432), Redis disponible en `bin/redis`.
- Docker Desktop: **iniciar el daemon** (estaba apagado) para servicios como pgvector/N8N cuando apliquen.
- Git 2.47.

### 1.2 Checklist inicial

1. Iniciar Laragon y su PostgreSQL 16 (verificar `pg_isready`).
2. Crear base de datos `pdi_saas` (y `pdi_saas_test` para tests).
3. Activar Redis de Laragon (`bin/redis/redis-server.exe` o el switch de Laragon) para cache/queues.
4. Decidir y anotar en `04` el enfoque local de pgvector (Pendiente).

---

## 2. Creación del proyecto Laravel

```bash
# en la raíz del repo (docs ya existe)
composer create-project laravel/laravel . --prefer-dist   # o scaffolding en subcarpeta si se prefiere
```

- Añadir dependencias base:
  - `laravel/folio` o rutas clásicas (según estilo) para páginas públicas.
  - `inertiajs/inertia-laravel` + `@inertiajs/vue3` + Vue 3 + Tailwind + Vite.
  - `filament/filament` para el backoffice del staff.
  - `php-openai/openai` (o SDK elegido) para la capa `AiProvider`.
  - `opentoutai/laravel-lang` opcional; librerías de parsing según `06`.
- Configurar `.env`: conexión Postgres, Redis, `APP_URL`, credenciales IA (OpenRouter), R2.

---

## 3. Base de datos y multi-tenancy (primera semana)

### 3.1 Migraciones (orden)

1. `tenants`, `users`, `tenant_user`, `domains`, `plans`, `model_profiles`.
2. Habilitar extensión `pgvector` (`CREATE EXTENSION IF NOT EXISTS vector`).
3. Tablas de negocio del MVP1: `websites`, `pages`, `page_blocks`, `media`, `knowledge_sources`, `knowledge_documents`, `knowledge_chunks` (+ columna `embedding vector(1536)`).
4. `conversations`, `messages`, `contacts`, `leads` (preparados para MVP2).

### 3.2 RLS

- Migración/recurso que aplica `ENABLE ROW LEVEL SECURITY` + políticas `USING`/`WITH CHECK` sobre todas las tablas de negocio, usando `current_setting('app.tenant_id')`.
- Roles de BD: `app` (sin BYPASSRLS), `migrator` (DDL), `admin_platform` (restringido).
- Helper de modelo: trait `TenantScoped` que añade el índice/castea tenant_id; middleware `SetTenantContext` en web y api.

### 3.3 Test de fuga cross-tenant (CI, desde el día 1)

- Test que crea 2 tenants y verifica que el segundo **no puede** leer/escribir datos del primero (modelo directo + vía HTTP).
- Se ejecuta en CI (GitHub Actions) junto a `php artisan test`.

---

## 4. Orden de implementación por épica (ver `09`)

```
E1 Multi-tenant/auth  →  E3 Custom Domains  →  E2 Builder  →  E4 Knowledge Base
→ E5 RAG  →  E6 Web Chat  →  E7 Dashboard   [MVP 1 entregable]
→ E8 CRM → E9 Memory → E10 WhatsApp → E11 Analytics → E12 Billing   [MVP 2]
→ E13-17 Agents/Tools/N8N/Email/Social       [MVP 3]
```

### 4.1 Sugerencia de arranque

1. **E1**: auth + tenants + roles + `SetTenantContext` + RLS + test de fuga. *Sin esto no se construye nada encima.*
2. **E3**: `domains` + middleware de resolución Host→tenant (permite servir cualquier tenant por dominio; clave comercial).
3. **E2**: template único + componentes esenciales + render SSR.
4. **E4/E5**: pipeline RAG en cola (parser → chunks → embeddings → pgvector) + endpoint de chat con retrieval.
5. **E6**: widget público con streaming.

---

## 5. Capa de IA (`AiProvider`)

- Interfaz: `chat()`, `stream()`, `embed()`, `models()`.
- Implementaciones: `OpenRouterProvider` (chat/stream vía API compatible OpenAI) y `EmbeddingProvider` directo.
- Perfiles de tarea en `model_profiles` → routing por complejidad y presupuesto.
- Registro de `ai_runs` en cada llamada (tokens, costo, latencia, caché).
- Failover: si un proveedor falla, reenviar al siguiente perfil.

---

## 6. CI/CD y deploy

### 6.1 GitHub Actions

- Jobs: `lint` (Pint), `test` (Pest/PHPUnit incl. fuga cross-tenant), `build assets`, `deploy`.

### 6.2 Docker Compose (Hetzner)

```
services:
  app:       imagen PHP-FPM + Nginx (assets compilados en build)
  db:        pgvector/pgvector:pg16  (volumen persistente, backups → R2)
  redis:     redis:7-alpine
  n8n:       n8nio/n8n (desde Fase 3; token por tenant)
  worker:    php artisan queue:work (misma imagen app)
```

- Cloudflare: DNS proxied del dominio plataforma + custom domains de clientes (SSL automático).
- Backups: `pg_dump` diario + WAL → R2; test de restauración mensual.

---

## 7. QA y criterios de release

- [ ] Test de fuga cross-tenant verde.
- [ ] Pipeline RAG procesa PDF/DOCX/URL sin romper.
- [ ] Chat responde con conocimiento y deriva a contacto si no sabe (sin inventar).
- [ ] 2 tenants publicados con dominios distintos en producción.
- [ ] `ai_runs` registrados y revisados (costos reales por tenant).
- [ ] Backup restaurado con éxito al menos una vez.

---

## 8. Riesgos técnicos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| RLS mal aplicada en una tabla nueva | Convención + lint de migraciones + test de fuga automático por tabla |
| Costo de embeddings en ingesta masiva | Batch + límites por plan + reprocesar solo documentos modificados |
| Streaming en PHP | SSE / respuestas en streaming desde `AiProvider`; probar con proxies (Nginx buffering off) |
| pgvector en local | Docker `pgvector/pgvector:pg16` cuando se trabaje RAG (decisión en `04`) |
| Subida de documentos grandes | Límites de tamaño/tiempo + jobs en cola + timeout HTTP corto para subida directa a R2 |
| SEO en render SSR | Inertia renderiza SSR (Laravel) o Blade para páginas públicas; `meta` por página |

---

## 9. Próximos pasos inmediatos (al finalizar documentación)

1. Decidir el enfoque local de pgvector y Redis (anotar en `04`).
2. `git init` + primer commit con `docs/`.
3. Crear proyecto Laravel (sección 2).
4. Implementar E1 (multi-tenant + RLS + test de fuga).
5. Mientras tanto, decidir: modelo de embeddings definitivo y mercado/pricing (`09`).
