# 05 — Data Model

**AI Business Platform** · Entidades y relaciones en PostgreSQL (shared schema + RLS)

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: `03-Domain-Multi-Tenancy.md`, `04-Architecture.md`
- **Estado**: Borrador de referencia para migraciones

---

## 1. Convenciones

- Toda tabla de negocio (tenant-scoped) tiene columna `tenant_id BIGINT NOT NULL REFERENCES tenants(id)` + RLS habilitada.
- Tablas **globales** (sin `tenant_id`): `tenants`, `users`, `roles`, `domains` (enrutamiento), `plans`, `model_profiles`, `audit_logs`.
- `id` UUID v7 en tablas expuestas públicamente; `BIGSERIAL` en internas.
- Timestamps estándar `created_at` / `updated_at`; `deleted_at` para soft deletes.
- Índice compuesto con `tenant_id` al frente en toda tabla tenant-scoped.

---

## 2. Diagrama de entidades

```
tenants ──< domains                    (custom domains → tenant)
tenants ──< website                    (1:1, sitio del tenant)
website  ── pages (jsonb)              (páginas como config JSON, ver §3.2)
tenants ──< business_profiles          (1:1, perfil comercial que hidrata el sitio)
tenants ──< users (pivot)              (usuarios con rol por tenant)
tenants ──< knowledge_documents ──< knowledge_chunks ──[embedding vector]
tenants ──< knowledge_sources          (URLs, FAQs, texto manual)
tenants ──< contacts ──< conversations ──< messages
contacts ──< leads ──< deals           (pipeline comercial)
contacts ──< customer_memory           (resúmenes estructurados)
tenants ──< agents ──< agent_runs
tenants ──< integrations               (canales/credenciales cifradas)
tenants ──< ai_runs                    (costo/trazabilidad por llamada)
tenants ──< subscriptions
tenants ──< analytics_events
```

---

## 3. Entidades principales

### 3.1 Globales (sin RLS)

**tenants**
| campo | tipo | notas |
|---|---|---|
| id | uuid pk | |
| name | text | |
| slug | text unique | subdominio plataforma |
| industry | text | vertical |
| country | text | |
| plan_id | fk → plans | |
| status | enum | active / suspended / trial / cancelled |
| settings | jsonb | branding, límites |
| timestamps | | |

**users**
| campo | tipo | notas |
|---|---|---|
| id | uuid pk | |
| name / email | text | email unique |
| password_hash | text | |
| is_platform_admin | bool | |
| timestamps | | |

**tenant_user** (pivot, tenant-scoped)
| campo | tipo |
|---|---|
| tenant_id | fk |
| user_id | fk |
| role | enum: owner / editor / operator / agency |

**domains** (tabla de enrutamiento; **RLS disabled**, acceso restringido)
| campo | tipo | notas |
|---|---|---|
| id | uuid pk | |
| tenant_id | fk → tenants | |
| host | text unique | ej. www.empresa.com |
| is_primary | bool | |
| verified_at | timestamptz null | |
| status | enum | pending / verified / suspended |

**plans** (catálogo global)
| id | name | slug | price_monthly | limits (jsonb: docs, pages, channels, ai_budget) |

**model_profiles** (catálogo de perfiles de IA global)
| id | slug | provider | model | purpose | max_input_tokens |

### 3.2 Website (tenant-scoped)

**websites**
| tenant_id | id | name | template | theme (jsonb: logo, colores, tipografías) | pages (jsonb) | status (draft/live) | published_at |

> **Decisión (MVP1)**: el sitio no se modela como tablas normalizadas `pages`/`page_blocks`. Se guarda **config JSON estructurada** en `websites.pages`: array de páginas, cada una con `{ slug, title, meta, sections: [ { type, variant, content } ] }`. `type` referencia el catálogo de bloques (`config/site.php → catalog.blocks`), `theme` y `template` fijan el diseño. El frontend (Component Registry Vue) renderiza cada `type`; nunca se guarda HTML por cliente. `business_profiles` es la fuente única que hidrata el contenido de los bloques (web, RAG, chat, CRM, agentes).

**business_profiles**
| tenant_id | id | name | tagline | description | logo_url | industry | services (jsonb) | products (jsonb) | branches (jsonb) | schedule (jsonb) | contact (jsonb) | social (jsonb) | faqs (jsonb) | team (jsonb) | certifications (jsonb) |

**media**
| tenant_id | id | file_key (R2) | url | mime | size | alt |

> **Nota**: las antiguas tablas normalizadas `pages` y `page_blocks` (borrador v1) quedaron descartadas en el MVP1 a favor de la config JSON en `websites.pages`. Si un tenant necesita editorial complejo (múltiples páginas SEO pesadas), se puede migrar puntualmente a tablas sin cambiar la app.

### 3.3 Knowledge (tenant-scoped)

**knowledge_sources**
| tenant_id | type (upload/url/faq/manual) | title | status | meta |

**knowledge_documents**
| tenant_id | source_id | filename | mime | storage_key | status (pending/processing/ready/error) | chunk_count | error |

**knowledge_chunks**
| tenant_id | document_id | chunk_index | content (text) | token_count | source_ref (página/cita) | **embedding vector(1536)** | |

> `embedding` usa pgvector. Filtro RLS por `tenant_id`. Índice IVFFlat o HNSW sobre embedding con `WHERE tenant_id = ?` obligatorio en búsqueda.

### 3.4 Conversaciones y CRM (tenant-scoped)

**contacts**
| tenant_id | name | email | phone | whatsapp_id | instagram_username | tags | lifecycle (lead/customer/...) | consent_status | memory_summary (jsonb) | last_activity_at |

**conversations**
| tenant_id | contact_id | channel (web/whatsapp/instagram/facebook/email) | external_channel_id | subject | status (open/closed/pending_human) | started_at | ended_at |

**messages**
| tenant_id | conversation_id | direction (in/out) | author_type (visitor/agent/human/system) | content | attachments (jsonb) | created_at |

**leads**
| tenant_id | contact_id | source_channel | intent | lead_score | status (new/qualified/negotiation/won/lost) | next_action | assigned_user_id |

**deals** (opcional, fase CRM avanzada)
| tenant_id | lead_id | amount | stage | expected_close_at | notes |

**customer_memory**
| tenant_id | contact_id | kind (summary/preferences/interests) | content (jsonb/text) | window_start | window_end | policy (retención) |

### 3.5 IA (tenant-scoped)

**agents**
| tenant_id | slug (sales/support/reception/booking/followup) | name | instructions (text) | tools (jsonb: catálogo de tools habilitadas) | model_profile_id | is_active | guardrails (jsonb) |

**agent_runs**
| tenant_id | agent_id | conversation_id | status | tokens_in | tokens_out | cost | started_at | finished_at | trace (jsonb, acciones) |

**ai_runs** (trazabilidad y facturación de uso)
| tenant_id | trigger (chat/agent/tool/summary) | model_profile_id | tokens_in | tokens_out | cost_usd | latency_ms | cached (bool) | created_at |

### 3.6 Integraciones (tenant-scoped)

**integrations**
| tenant_id | channel (whatsapp/instagram/facebook/email/n8n/...) | provider | config_encrypted (text cifrado) | status | webhook_secret | last_sync_at |

**webhook_outbox**
| tenant_id | event (lead.created/conversation.closed/...) | payload (jsonb) | status (pending/sent/failed) | attempts | next_attempt_at | response_code |

### 3.7 Billing y analytics

**subscriptions**
| tenant_id | plan_id | status | current_period_start/end | provider_ref (Stripe) | ai_usage_billed |

**analytics_events**
| tenant_id | kind (page_view/chat_message/lead_generated/...) | context (jsonb) | created_at |

**audit_logs** (global, por plataforma)
| id | actor_user_id | tenant_id nullable | action | entity | before/after (jsonb) | ip | created_at |

---

## 4. Índices recomendados

- `(tenant_id, created_at DESC)` en todas las tablas de negocio.
- `(tenant_id, contact_id, created_at)` en `messages`.
- `(tenant_id, status)` en `conversations` y `leads`.
- HNSW index sobre `knowledge_chunks(embedding)`.
- `domains(host)` unique; `tenants(slug)` unique; `users(email)` unique.

---

## 5. Políticas RLS (resumen)

- Habilitar RLS + política `USING`/`WITH CHECK` con `current_setting('app.tenant_id')` en **todas** las tablas de negocio.
- Tablas globales: RLS disabled, acceso solo por servicio con autorización explícita.
- `domains`: RLS disabled (enrutamiento) pero con control de acceso de la app (solo resolver dominio → tenant_id; nunca exponer datos).

---

## 6. Notas de evolución

- pgvector en la misma instancia: mantener dimensiones del modelo de embeddings fijas (1536 por defecto).
- Si un tenant supera un volumen crítico, migración puntual a schema-per-tenant (plan Enterprise) sin cambiar el modelo de la app.
- Los `customer_memory` se generan/expiran según políticas de retención (jobs programados).
