# 07 — Agent System

**AI Business Platform** · Agentes, herramientas, permisos y orquestación

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: `04-Architecture.md`, `05-Data-Model.md`, `06-AI-RAG-Memory.md`
- **Estado**: Referencia de diseño (Fase 4)

---

## 1. Concepto

De asistente (habla) a **agente** (habla y ejecuta). La diferencia es la capacidad de usar **herramientas**:

```
Pregunta → intención → consultar información → decidir → usar herramienta → ejecutar → confirmar resultado
```

El **AI Orchestrator** coordina modelos, agentes, herramientas y memoria. No hay un "agente monolítico": hay un orquestador + agentes especializados configurables.

---

## 2. AI Orchestrator

### 2.1 Responsabilidades

- Recibir la entrada de cualquier canal.
- **Detección de intención** (clasificación con modelo barato): soporte, ventas, agenda, seguimiento, general, escalar a humano.
- Elegir el **agente** adecuado (o responder directamente con RAG).
- **Routing de modelos** según complejidad y presupuesto.
- Ejecutar el **loop de herramientas** con límite de pasos.
- **Trazar** cada paso (`agent_runs`, `ai_runs`) y aplicar **guardrails**.
- Aplicar **budget por tenant** (tokens/costo) y limitar loops.

### 2.2 Loop de agente (tool-calling)

```
entrada → contexto (RAG + memoria) → LLM
  → ¿tool? Sí → validar permiso → ejecutar tool (trazabilidad) → continuar loop (máx. N pasos)
  → No → respuesta final en streaming
```

---

## 3. Agentes especializados

Cada agente tiene: `instrucciones`, `tools habilitadas`, `permisos`, `objetivos` y `triggers`.

| Agente | Propósito | Tools típicas | Triggers |
|---|---|---|---|
| **Reception Agent** | Primer contacto; saluda, responde general, identifica | RAG, FAQ, contacto humano | Mensaje nuevo sin intención específica |
| **Sales Agent** | Vende y cotiza | RAG (catálogo), calculadora de cotización, crear lead, enviar PDF, agenda | Intención de compra / cotización |
| **Support Agent** | Resuelve dudas y problemas | RAG, guías, escalado humano, seguimiento | Preguntas de postventa/garantía |
| **Booking Agent** | Agenda citas/reservas | Calendario (disponibilidad), crear reserva, recordatorio | Pedido de cita/reserva |
| **Follow-up Agent** | Persigue oportunidades | CRM (leads), WhatsApp/email templated, tareas | Lead sin respuesta N días |
| **Post-sales Agent** | Fideliza y referencias | CRM, encuestas, ofertas | Cliente reciente |

- Los agentes **se configuran por tenant** (activos, tools, instrucciones, canales).
- El MVP1 usa solo el orquestador + flujo de chat con RAG (sin agentes especializados); los agentes llegan en Fase 4.

---

## 4. Herramientas (Tools)

### 4.1 Catálogo de tools del core

| Tool | Descripción | Efecto |
|---|---|---|
| `knowledge_search` | Consulta RAG del tenant | Contexto |
| `catalog_lookup` | Busca productos/servicios en el catálogo | Precios, disponibilidad |
| `quote_calculator` | Calcula cotización con reglas del tenant | Importe |
| `generate_quote_pdf` | Genera PDF de cotización | Documento (R2) |
| `create_lead` | Crea/actualiza lead en CRM | Lead con score e intención |
| `create_task` | Crea tarea/seguimiento | Tarea |
| `send_message` | Envía mensaje por canal (WhatsApp/email templated) | Mensaje saliente |
| `booking` | Consulta/crea reserva en calendario | Reserva |
| `n8n_webhook` | Dispara workflow externo en N8N | Acción externa |
| `notify_human` | Escala a un humano (bandeja) | Alerta operador |

### 4.2 Contratos de tools

- Definidas como clases PHP con: `name`, `description` (para el LLM), `schema` de parámetros (JSON Schema), `permission_level`, `execute()`.
- El resultado de `execute()` se devuelve al LLM como texto estructurado (JSON corto).
- **Trazabilidad**: cada invocación se registra (tool, input, output resumido, timestamp, agente) en `agent_runs.trace` y `audit_logs`.

### 4.3 Permisos por tool

| Nivel | Ejemplo | Autonomía |
|---|---|---|
| Lectura | `knowledge_search`, `catalog_lookup` | Siempre permitida |
| Escritura interna | `create_lead`, `create_task` | Permitida con registro |
| Escritura externa / envío | `send_message`, `n8n_webhook`, `generate_quote_pdf` | Requiere confirmación o política del tenant ("auto" vs "aprobar") |
| Destructiva | `delete_*`, `cancel_booking` | Nunca automática en MVP |

- Política por tenant: `auto_actions` (enumeración de tools que ejecuta sola) o modo "aprobar humano".

---

## 5. Guardrails y seguridad

1. El agente **nunca** expone instrucciones internas, tools, ni datos de otros tenants.
2. Las acciones sensibles requieren confirmación según política del tenant.
3. **Humano en el loop**: umbrales que escalan a bandeja (intención negativa, pedido de humano, alto valor, dudas de identidad).
4. **Límite de pasos**: máx. 8 tool calls por turno (evitar loops).
5. **Budget**: límite de tokens/costo por tenant/mes; si se agota → chat básico o alerta.
6. **Trazabilidad completa**: todo agente ejecuta bajo `agent_runs` con trace y costo.

---

## 6. Routing de modelos (perfil por tarea)

| Perfil | Modelo sugerido (2026) | Uso |
|---|---|---|
| `fast` | Gemini 2.5 Flash | Clasificación, resúmenes, chat alto volumen |
| `default` | GPT-4o-mini / Gemini Flash | Asistente general con RAG |
| `agent` | Claude Sonnet 4.6 / GPT-5.4-mini | Agentes complejos, tool-calling, cotizaciones |
| `embed` | OpenAI text-embedding-3-small | Embeddings |

- Perfiles en tabla global `model_profiles`; la capa `AiProvider` los mapea a proveedor/modelo real (OpenRouter o directo) con failover.

---

## 7. Bandeja de operadores (humano en el loop)

- Conversaciones escaladas aparecen en la bandeja del tenant con contexto (resumen IA, intención, lead).
- El operador puede: responder directamente, devolver a la IA, cerrar, o convertir en tarea.
- El historial unificado se mantiene (el cliente no pierde contexto al pasar a humano).

---

## 8. Criterios de éxito

- [ ] Un agente ejecuta el flujo "cotización" completo: requisitos → catálogo → cálculo → PDF → lead → envío → seguimiento.
- [ ] Acciones destructivas/externas controladas por política (auto vs aprobar).
- [ ] Toda acción registrada y auditable.
- [ ] Budget por tenant respetado; loops limitados.
- [ ] Escalamiento a humano con contexto completo.
