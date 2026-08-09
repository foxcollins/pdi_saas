---
name: product-docs
description: Workflow para la documentación de producto de PDI_SAAS. Use cuando el usuario pida crear, actualizar o avanzar una fase de la documentación del proyecto (visión, requisitos, multi-tenancy, arquitectura, datos, IA/RAG, agentes, integraciones, backlog, implementación). También use cuando se mencione crear o editar archivos en docs/.
---

# Product Docs — PDI_SAAS

Documentación de producto en español, numerada por fase en `docs/`.

## Orden de fases

1. `01-Vision.md` — visión, misión, problema, propuesta de valor, principios, usuarios, actores, casos de uso, diferenciadores, límites, visión a 3 años, North Star, hipótesis, decisiones arquitectónicas.
2. `02-Product-Requirements.md` — requisitos funcionales y no funcionales por módulo.
3. `03-Domain-Multi-Tenancy.md` — estrategia de tenant, aislamiento (RLS), custom domains.
4. `04-Architecture.md` — arquitectura técnica y stack (Laravel + Inertia/Vue, Postgres + pgvector, Redis, OpenRouter, N8N, Hetzner/Cloudflare).
5. `05-Data-Model.md` — entidades y relaciones PostgreSQL.
6. `06-AI-RAG-Memory.md` — pipeline de conocimiento, embeddings, retrieval, memoria.
7. `07-Agent-System.md` — agentes, tools, permisos, orquestación.
8. `08-Integrations.md` — WhatsApp, Social, Email, N8N, APIs.
9. `09-MVP-Backlog.md` — épicas, historias, pricing y roadmap de implementación.
10. `10-Implementation.md` — plan de ejecución técnica.

## Reglas

- Cada fase se construye sobre la anterior; no saltar fases.
- Antes de codificar una fase, primero su documento en `docs/`.
- Los documentos se escriben en español.
- Respetar las decisiones fijadas en `01-Vision.md` y en `AGENTS.md`
  (stack Laravel, shared schema + RLS, capa `AiProvider`, N8N, etc.).
- `00-macro-idea.docx` es solo referencia conceptual, no la fuente de verdad.
- Mantener trazabilidad con el macro y con los documentos anteriores.
