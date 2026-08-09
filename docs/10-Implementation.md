# 10 — Implementation

**AI Business Platform** · Plan de ejecución técnica

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: `04-Architecture.md`, `05-Data-Model.md`, `09-MVP-Backlog.md`
- **Estado**: Guía de arranque e implementación

---

## 1. Preparación del entorno local

### 1.1 Requisitos (Laragon + Docker)

- Laragon: PHP 8.2+ (actualmente 8.2.17), Composer 2.5, Node 22, **PostgreSQL 16 nativo** (5432/5433, se deja libre para otros proyectos).
- Docker Desktop: **Postgres 16 + pgvector y Redis** vía `docker compose up -d` (Postgres publicado en **54329** para evitar conflicto con el de Laragon).
- Git 2.47.

### 1.2 Checklist inicial

1. Iniciar Docker Desktop y levantar servicios: `docker compose up -d` (db en `54329`, redis en `6379`).
2. Configurar `.env`: `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=54329`, `DB_DATABASE=pdi_saas`, `DB_USERNAME=pdi`, `DB_PASSWORD=pdi_secret`.
3. Crear base de datos `pdi_saas_test` para tests cuando se active el pipeline CI.
4. pgvector ya disponible en la imagen `pgvector/pgvector:pg16` (decisión en `04`): crear la extensión en la migración cuando se trabaje RAG.

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

---

## 10. Estado de implementación (Agosto 2026)

### 10.1 Hecho (entorno local, fake provider)

- Proyecto Laravel creado con Inertia + Vue 3 + Tailwind + Postgres 16 + pgvector (Docker, puerto 54329) + Redis (Docker, 6379).
- **E1 multi-tenant/auth**: RLS habilitado por tabla + rol BD `app_tenant` sin BYPASSRLS (comando `app:setup-db`) + `TenantScope` (setea `app.tenant_id`); registro de tenant crea `user` + `tenant` (slug único) + `business_profile` + `website` (template `minimal-business`) + `agent`.
- **E3 custom domains**: `DomainResolver` normaliza Host (sin `www.`, sin puerto) y resuelve dominio verificado o subdominio plataforma; `GET /` sirve el sitio del tenant según Host (o la Landing si no resuelve); UI de Domains completa (agregar, verificación TXT, principal, eliminar). Verificación TXT automática en cola: `DnsTxtVerifier` (DNS-over-HTTPS con fallback a `dns_get_record`, token aleatorio `_pdi-verify.<host>`), job `VerifyDomainTxt`, scheduler `domains:verify-pending` cada 5 min + verificación bajo demanda. Tests de resolución + servicio por Host (6 tests). Pendiente: wildcard vhost local.
- **E2 builder**: panel visual completo (`/app/builder`) con catálogo de bloques (`config/site.php → catalog.blocks`), templates, preview, edición de `content` por bloque, editor de theme, guardar/publicar/despublicar, reset template, y modales "Crear con IA" / "Refinar con IA" (`/app/ai/generate`, `/app/ai/refine`). Render público con `PublicSite.vue` + `ChatWidget` + banner de borrador.
- **E4/E5 knowledge + RAG**: subida por texto/URL/archivo, pipeline asíncrono (parse → chunk → embeddings → pgvector), retrieval con filtro tenant. Parsers: PDF (smalot), DOCX/XLSX/PPTX (extractor OOXML vía ZipArchive+XML, `OfficeTextExtractor`), texto/URL. Mimes permitidos validados en upload.
- **E6 web chat**: SSE streaming con respuesta basada en conocimiento o derivación a contacto.
- **E7 dashboard**: páginas del panel (dashboard, builder, content, knowledge, domains, chats, leads). Métrica "preguntas sin respuesta" (consulta de chat sin resultados de RAG, `AnalyticsEvent kind=unanswered_question`) en `DashboardController` + tarjeta/lista en `Dashboard.vue`. Tests en `DashboardMetricsTest` (3 tests).
- **E8 CRM**: `/app/crm` con pestañas Bandeja/Pipeline/Contactos/Tareas/Notas; escalamiento automático de conversaciones sin respuesta (`conversations.needs_human`, `escalated_at`) desde `ChatService`; kanban de leads (new→qualified→negotiation→won→lost); perfil de contacto con historial unificado (`/app/crm/contacts/{id}`); notas y tareas con RLS habilitado en las nuevas tablas (`notes`, `tasks`). Tests en `CrmTest` (4 tests).
- **E9 memory**: `MemoryService` extrae preferencias/intereses de conversaciones (regex), consolida en `contacts.memory_summary`, gestiona consentimiento y anonimización/olvido (borra memoria + mensajes + conversaciones + leads, marca `contacts.anonymized_at`); política de retención configurable (`config/memory.php`, 365 días) con comando `memory:prune` diario en scheduler. Página `/app/memory`. Tests en `MemoryTest` (5 tests).
- **Lead capture**: `POST /api/contact` crea Contact + Conversation + Message + Lead + AnalyticsEvent.

### 10.2 Decisiones registradas

- **Website Builder = config JSON estructurada** en `websites.pages` (`{ type, variant, content }` + `theme` + `template`), renderizada por un Component Registry Vue. No se guarda HTML por cliente. Ver `04 §3.4` y `05 §3.2`.
- **Business Profile = fuente única** que hidrata web, RAG, chat, CRM y agentes.
- **Local sin claves de IA**: `AI_DEFAULT_PROVIDER=fake` (FakeProvider con respuestas deterministas que responden desde el conocimiento). `QUEUE_CONNECTION=sync`. Sesión/cache en `database` (Laragon sin `php_redis.dll`).
- **Relaciones de conocimiento** corregidas a las columnas reales (`source_id`, `document_id`) y mutator de `embedding` que serializa arrays a literal `[...]` para pgvector (el binding array de PDO no aplica a `vector`).

### 10.3 Verificación end-to-end (smoke tests HTTP)

- Rutas públicas y del panel con login demo (`demo@andina.com`): todas 200.
- `POST /app/builder/save` → `200 {"ok":true}`; `/app/ai/generate` → `200 ok=True`.
- `/api/chat/{slug}` → SSE (`type:start`, `type:chunk`) respondiendo horario/contacto desde knowledge.
- `/api/contact/{slug}` → crea lead + conversación + analytics.
- Registro de tenant por HTTP (slug único) → dashboard + builder operativos.
- `php artisan test` → 2 passed; `php -l` limpio en archivos modificados; `npm run build` exitoso.

### 10.4 Pendiente

- Custom domains (E3): wildcard vhost local en Laragon (resolución por Host, UI y verificación TXT automática ya funcionan).
- Tests automatizados: fuga cross-tenant (RLS), RetrievalService/RAG, ChatService, BuilderController (save/publish), ContactApi, DomainResolver y verificación DNS (DoH mock, job, controlador), DashboardMetrics, Crm, Memory. Base `pdi_saas_test` en 54329. **Hecho**: 47 tests verdes (fuga app+RLS, RAG, chat, builder, contacto, dominios, DNS, dashboard, CRM, memoria).
- Commit del estado actual y actualización continua de `docs/`.
