# AGENTS.md — PDI_SAAS

Plataforma SaaS de Presencia Digital Inteligente (AI Business Platform).

## Idioma y documentación

- Todos los documentos técnicos y de producto se escriben en **español**.
- La documentación vive en `docs/`, numerada por fase:
  `00-macro-idea.docx` (fuente), `01-Vision.md`, `02-Product-Requirements.md`,
  `03-Domain-Multi-Tenancy.md`, `04-Architecture.md`, `05-Data-Model.md`,
  `06-AI-RAG-Memory.md`, `07-Agent-System.md`, `08-Integrations.md`,
  `09-MVP-Backlog.md`, `10-Implementation.md`.
- La fuente de verdad de cada fase es `docs/0N-<fase>.md`; el `00-macro-idea.docx`
  es solo referencia conceptual.

## Stack (fijado en 01-Vision.md)

- **Backend**: Laravel (PHP) monoestack.
- **Frontend**: Inertia + Vue 3 + Tailwind.
- **Base de datos**: PostgreSQL 16 + pgvector (una sola instancia).
- **Multi-tenancy**: shared schema + Row-Level Security (RLS) con `tenant_id`;
  test de fuga cross-tenant en CI desde el día 1.
- **Custom domains**: enrutamiento por `Host` → tenant → render.
- **Cache/queues**: Redis.
- **IA**: capa `AiProvider` agnóstica con routing por costo/calidad; OpenRouter
  como gateway multi-proveedor; embeddings directos.
- **Automatización**: N8N self-hosted como capa de acción/integraciones.
- **Deploy**: Docker Compose en VPS (Hetzner) + Cloudflare (CDN, SSL, R2).
- **Local**: Laragon (PHP/Postgres) + Docker solo para servicios que Laragon
  no cubre (pgvector, N8N).

## Convenciones de código

- No agregar comentarios al código salvo que se pida explícitamente.
- Seguir los patrones y librerías ya presentes en el proyecto (nunca asumir
  que una librería está disponible; verificar antes de usar).
- Regla de oro multi-tenant: toda consulta de datos de tenant pasa por RLS y
  nunca se omite el contexto de tenant (incluidos jobs, webhooks y comandos).

## Comandos

- Lint/typecheck: pendientes de definir cuando exista código (se completará
  aquí al crear el proyecto Laravel).
- Tests: pendientes de definir.

## Flujo de trabajo

- Antes de codificar una fase, escribir/actualizar primero su documento en
  `docs/` (orden: visión → requisitos → dominio/multi-tenancy → arquitectura →
  datos → IA → agentes → integraciones → backlog → implementación).
- Cualquier cambio de config de opencode (`opencode.json`, agentes, skills,
  MCP) requiere reiniciar opencode para que surta efecto.
