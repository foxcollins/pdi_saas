# 04 — Architecture

**AI Business Platform** · Arquitectura técnica y stack

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: `01-Vision.md`, `02-Product-Requirements.md`, `03-Domain-Multi-Tenancy.md`
- **Estado**: Aprobado (stack fijado en la visión)

---

## 1. Principios arquitectónicos

1. Monolito único (máxima simplicidad operativa para un equipo pequeño).
2. Núcleo genérico + módulos/plugins; el core no cambia al agregar capacidades.
3. IA agnóstica de proveedor (`AiProvider`) con routing por costo/calidad.
4. Multi-tenant: shared schema + RLS (ver `03`).
5. Todo trabajo asíncrono y pesado en colas (Redis).
6. La web del cliente se sirve desde el mismo monolito (render en servidor, SEO-friendly).
7. La automatización externa se apoya en N8N para no implementar cada integración en el core.
8. Deploy reproducible en Docker Compose sobre un VPS (Hetzner) con Cloudflare delante.

---

## 2. Arquitectura en capas

```
 USERS / VISITORS
        │
 ┌──────▼───────┐
 │ CHANNEL LAYER│  Web pública (SSR) · Widget chat · WhatsApp · Instagram · Facebook · Email · APIs
 └──────┬───────┘
        ▼
 ┌──────────────────────┐
 │   AI ORCHESTRATOR    │  Routing de modelos · agentes · herramientas
 └──────┬───────────────┘
        ▼
 ┌──────────────────────┐
 │ INTELLIGENCE LAYER   │  RAG (pgvector) · Memoria · CRM · Analytics
 └──────┬───────────────┘
        ▼
 ┌──────────────────────┐
 │    AI MODELS         │  OpenRouter (multi-proveedor) · Embeddings directos
 └──────┬───────────────┘
        ▼
 ┌──────────────────────┐
 │      TOOLS           │  Catálogo · Cotización · Agenda · CRM · Envíos
 └──────┬───────────────┘
        ▼
 ┌──────────────────────┐
 │ ACTION LAYER         │  N8N · APIs · Sistemas internos/externos
 └──────────────────────┘
```

---

## 3. Stack

### 3.1 Aplicación (monolito)

| Componente | Tecnología | Notas |
|---|---|---|
| Framework | **Laravel** (PHP 8.3+) | API + admin + render web + jobs |
| Frontend | **Inertia + Vue 3 + Tailwind** | App admin y builder (Component Registry, §3.4) |
| Admin interno | **Filament** (donde aplique) | Backoffice rápido del staff |
| Base de datos | **PostgreSQL 16 + pgvector** | Datos + vectores en una instancia |
| Cache / colas | **Redis** | Cache, sesiones, queues |
| Queues/jobs | **Laravel Horizon** | Pipeline de conocimiento, embeddings, webhooks |
| Autenticación | **Laravel Sanctum + Breeze** | API tokens, sesiones |
| Billing | **Laravel Cashier (Stripe)** | Fase posterior |
| Dominios | Middleware propio + tabla `domains` | Resolución Host → tenant |

### 3.2 IA

| Componente | Tecnología | Notas |
|---|---|---|
| Capa de abstracción | `AiProvider` (interfaz propia) | `chat()`, `embed()`, `stream()` |
| Gateway de modelos | **OpenRouter** | Una API OpenAI-compatible para muchos proveedores |
| Routing por tarea | Config por perfil | Barato → alta escala; calidad → agentes |
| Embeddings | Proveedor directo (ej. OpenAI `text-embedding-3-small`) | No requiere OpenRouter |
| RAG | pgvector (consulta SQL híbrida + filtro tenant) | Sin infra extra |

### 3.3 Infraestructura

| Componente | Tecnología |
|---|---|
| Servidor | **VPS Hetzner** (CX33: 2 vCPU / 8 GB / 80 GB NVMe — referencia) |
| Contenedores | **Docker Compose**: `app` (PHP-FPM+Nginx), `postgres+pgvector`, `redis`, `n8n` |
| Edge | **Cloudflare**: CDN, SSL, custom domains, R2 (storage), email routing |
| Storage | **Cloudflare R2** (S3-compatible, sin egress) |
| Backups | Postgres automático (WAL + dumps) → R2 |
| Monitoreo | logs estructurados + metricas básicas (Sentry/Uptime Kuma) |

### 3.4 Website Builder (decisión MVP1)

- **El producto es configuración, no HTML por cliente.** Cada sitio es un documento JSON estructurado guardado en `websites` (`template` + `theme` + `pages[].sections[]` con `{ type, variant, content }`), no markup.
- **Catálogo de bloques** en `config/site.php` (`catalog.blocks`): lista de tipos (hero, navbar, services, products, gallery, faq, contact, cta, footer, chat, etc.) con sus props por defecto y variantes.
- **Component Registry (Vue)**: un mapa `{ [type]: Component }` en el frontend renderiza cada bloque tanto en el panel (preview + edición de `content` con props genéricos) como en el sitio público (`PublicSite.vue`). El `theme` (colores/tipografía/logo) se aplica como variables CSS a todos los bloques.
- **Business Profile es la fuente única**: el perfil comercial (`business_profiles`) hidrata el contenido por defecto de los bloques y alimenta web, RAG, chat, CRM y agentes. Editar el perfil propaga a todo el sistema.
- **Persistencia**: el builder guarda vía `POST /app/builder/save` (CSRF) todo el JSON del sitio; `status` `draft/live` + `published_at` controlan el render público.
- **IA del builder**: `POST /app/ai/generate` y `/app/ai/refine` construyen/modifican el JSON a partir del perfil y de instrucciones, usando la capa `AiProvider` (routing por costo/calidad).

---

## 4. Flujos clave

### 4.1 Request web de un tenant (custom domain)

```
Cloudflare → Nginx → app (middleware de dominio)
  → resolver Host → tenant → cargar config sitio → render SSR (Inertia/Blade) → respuesta
```

### 4.2 Conversación con el asistente (web chat)

```
Widget → POST /api/chat (stream)
  → Orchestrator: intención → recuperación RAG (pgvector, filtro tenant)
  → contexto (memoria del contacto) → LlmProvider.stream()
  → respuesta en streaming → registro de conversación (en cola) → analytics
```

### 4.3 Ingesta de conocimiento (asíncrono)

```
Subida (PDF/DOCX/URL/FAQs) → job "parsear" (cola)
  → extracción → limpieza → chunking → embeddings (batch) → upsert pgvector
  → estado del documento: listo/error → notificar al tenant
```

### 4.4 Acción de agente

```
Agente decide usar herramienta → registro del tool call (trazabilidad)
  → ejecutar tool (consulta catálogo, crear lead, webhook N8N)
  → confirmación → siguiente acción (envío PDF, seguimiento)
```

---

## 5. Estructura de carpetas propuesta

```
app/
  Http/
    Controllers/            # Panel, builder, sitio público, chat, contacto
    Middleware/             # SetTenantContext, ResolveTenant (Host → tenant)
  Services/
    Ai/Drivers/             # AiProvider + FakeProvider/OpenRouterProvider
    Knowledge/              # pipeline, chunking, retrieval (RAG)
    Chat/                   # ChatService (orquestación + contexto)
    Site/                   # builder, templates, blocks
  Models/                   # + TenantScope (RLS helper), models con casts JSONB
  Support/                  # TenantContext (set_config app.tenant_id), helpers
  Jobs/                     # parse-document, embed, send-webhook...
  Console/Commands/         # app:setup-db (roles/Rls/app_tenant)
docs/
  ...
```

---

## 6. Desarrollo local

### 6.1 Disponible

- **Laragon**: PHP 8.2, Apache/Nginx, **PostgreSQL 16 corriendo**, Redis (bin), Node 22, Composer, HeidiSQL, ngrok.

### 6.2 Regla

> Laragon = app y DB del día a día. Docker = solo servicios que Laragon no cubre (pgvector, N8N) y para reproducir el deploy.

### 6.3 Pendiente de decisión

- [x] **Postgres local**: **Postgres 16 + pgvector por Docker desde el día 1** (`pgvector/pgvector:pg16`), publicado en el host en el puerto **54329** para no chocar con el PostgreSQL 16 nativo de Laragon (que queda libre en 5432/5433). Redis por Docker en 6379.
- [x] **Redis**: activado vía Docker (`redis:7-alpine`, puerto 6379) al llegar a queues/cache.
- [ ] Definir hostnames locales: `*.test` de Laragon + ngrok para pruebas de webhook/WhatsApp.

---

## 7. Despliegue y CI/CD

1. **Git + CI (GitHub Actions)**: lint, tests (incl. fuga cross-tenant), build de assets.
2. **Docker Compose en Hetzner**: `docker compose up` con imágenes versionadas.
3. **Zero-downtime (nivel básico)**: dos contenedores app tras Nginx, migraciones antes de swap.
4. **Cloudflare**: proxying del dominio principal; custom domains de clientes proxied con SSL automático.
5. **Backups**: pg_dump diario + WAL → R2; prueba de restauración periódica.
6. **Migrations**: ejecutadas por el rol `migrator` (DDL), nunca por `app`.

---

## 8. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Fuga cross-tenant | RLS + test CI + roles BD sin BYPASSRLS + auditoría |
| Costo IA descontrolado | Budget por tenant, rate limits, routing barato, caching/batch |
| Alucinación del asistente | Modo knowledge-authorized + umbral de confianza + citas |
| Proveedor LLM caído | Failover multi-proveedor vía OpenRouter / directo |
| Deploy frágil | Docker Compose reproducible + migraciones versionadas + rollback |
| Escalabilidad de un solo VPS | RLS + índices; migración a managed Postgres (Neon/Supabase) o clúster solo si se superan límites |
