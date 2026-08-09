# 09 — MVP Backlog

**AI Business Platform** · Épicas, historias, pricing y roadmap de implementación

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: todas las fases anteriores
- **Estado**: Plan de trabajo

---

## 1. Estrategia de entregas

Tres entregables incrementales, cada uno comercializable:

1. **MVP 1 — AI Website**: demuestra la propuesta central.
2. **MVP 2 — Customer Intelligence**: convierte en plataforma.
3. **MVP 3 — Agents + Omnichannel**: convierte en AI Agent platform.

> Criterio: cada MVP termina **vendible**, no "más features".

---

## 2. MVP 1 — AI Website

**Propuesta**: "Puedo convertir una landing empresarial tradicional en una web inteligente que conoce el negocio."

### Épicas e historias

| Épica | Historias |
|---|---|
| **E1 · Multi-tenant y auth** | Alta de tenant; login; roles; panel del tenant; invitación de usuarios |
| **E2 · Website Builder** | Templates; componentes (Hero, Navbar, Servicios, Productos, Equipo, Testimonios, Galería, FAQ, Mapa, Contacto, CTA, Blog, Chat IA); branding; edición de contenido; SEO por página; publicar/borrador; render público SSR |
| **E3 · Custom Domains** | Dominio de plataforma por defecto; asignar dominio; verificación DNS; SSL; resolución Host→tenant; página "sin verificar" |
| **E4 · Knowledge Base** | Subir PDF/DOCX/XLSX/PPTX; ingestión URL; FAQs; texto manual; estados de procesamiento; lista/búsqueda/eliminación |
| **E5 · RAG** | Pipeline extracción→chunking→embeddings→pgvector; retrieval con filtro tenant; modo knowledge-authorized; umbral de confianza; derivación a contacto |
| **E6 · Web AI Chat** | Widget configurable; streaming; captura de contacto; registro de conversación; respuestas sin inventar |
| **E7 · Dashboard** | Métricas básicas del MVP (conversaciones, preguntas sin respuesta, leads captados); gestión de contenido y conocimiento |

### Definición de hecho (MVP 1)

- Dos tenants en paralelo no comparten datos (test cross-tenant verde).
- Un tenant publica su web con su dominio y conversa con su asistente usando su conocimiento.
- Pipeline RAG estable (documentos grandes y PDFs procesan en cola).
- Deploy en servidor (Hetzner) accesible públicamente.

---

## 3. MVP 2 — Customer Intelligence

**Propuesta**: el producto ya no es solo builder, es plataforma de clientes.

| Épica | Historias |
|---|---|
| **E8 · CRM conversacional** | Contactos; historial unificado; leads con score e intención; pipeline; notas/tareas; bandeja de escalamiento |
| **E9 · Memory** | Resúmenes por conversación; preferencias/intereses; políticas de retención/anonimización/eliminación; consentimiento |
| **E10 · WhatsApp** | Canal oficial (Meta) webhook→orquestador→respuesta; unificación por teléfono; plantillas básicas |
| **E11 · Analytics** | Métricas IA y comerciales; dashboard de leads y conversaciones |
| **E12 · Billing básico** | Planes; límites por plan; medidor de consumo IA; (Stripe en fase siguiente) |

### Definición de hecho (MVP 2)

- Un lead entra por WhatsApp o web y queda en CRM con historial unificado.
- La IA resume conversaciones y sugiere siguiente acción.
- Políticas de retención funcionando.

---

## 4. MVP 3 — Agents + Omnichannel

**Propuesta**: de AI Chat a AI Agent.

| Épica | Historias |
|---|---|
| **E13 · AI Orchestrator** | Intención; routing de modelos; loop de tools con límites; trazabilidad; budget por tenant |
| **E14 · Tools** | Catálogo, cotización + PDF, crear lead, tarea, mensaje, agenda, webhook N8N; permisos auto/aprobar |
| **E15 · Agentes** | Reception, Sales, Support, Booking, Follow-up configurables |
| **E16 · N8N** | Webhook outbox; workflows de ejemplo (lead→ERP/Calendar/Slack); tokens por tenant |
| **E17 · Email + Social** | Email transaccional/inbound; Instagram/Facebook DM |

### Definición de hecho (MVP 3)

- El flujo "cotización" completo se ejecuta con tools y queda auditado.
- Un workflow N8N recibe un lead y lo crea en un sistema externo.
- Escalamiento a humano con contexto.

---

## 5. Priorización y dependencias

```
E1 ─ E2 ─ E3 ─ E4 ─ E5 ─ E6 ─ E7     (MVP 1)
                     │
                     └─ E8 ─ E9 ─ E10 ─ E11 ─ E12   (MVP 2)
                                    │
                                    └─ E13 ─ E14 ─ E15 ─ E16 ─ E17   (MVP 3)
```

- **Crítico**: E1 (multi-tenant) y E5 (RAG) son el corazón; no postergar.
- E2 puede partir de un template mínimo (no aspirar a Webflow en MVP1).
- E3 (dominios) habilita la entrega comercial, no demorar.

---

## 6. Estimación inicial (referencia, por épica)

| Épica | Talla (S/M/L/XL) | Notas |
|---|---|---|
| E1 Multi-tenant/auth | L | Base crítica + tests cross-tenant |
| E2 Website Builder | XL | Mayor superficie de UI |
| E3 Custom Domains | M | Middleware + verificación + SSL |
| E4 Knowledge Base | M | Subida + parser + estados |
| E5 RAG | L | Pipeline + calidad |
| E6 Web Chat | M | Widget + streaming |
| E7 Dashboard | S | Primer corte |
| E8 CRM | L | Modelo + vistas |
| E9 Memory | M | Resúmenes + políticas |
| E10 WhatsApp | L | Meta + webhooks |
| E11 Analytics | M | Eventos + dashboards |
| E12 Billing | M | Planes + límites + consumo |
| E13-17 | L/L/M/M/M | Fase 3 |

---

## 7. Pricing y unit economics

### 7.1 Modelo

```
Setup inicial + suscripción mensual + consumo de IA + add-ons
```

| Plan | Incluye (referencia) |
|---|---|
| Starter | 1 dominio, web, IA básica, 1 canal |
| Business | + RAG completo, CRM, WhatsApp, 3 canales, memoria |
| Pro | + agentes, tools, N8N, analítica avanzada |
| Enterprise | infraestructura dedicada, integraciones avanzadas, SLA |

### 7.2 Estructura de costos a calcular

| Componente | Notas |
|---|---|
| Infraestructura (VPS Hetzner + Cloudflare + R2) | ~$15-25/mes fijos al inicio (ver `04`) |
| LLM | $50-300/mes temprano; por tenant recuperable vía consumo |
| WhatsApp (Meta) | ~$0.01-0.08/conversación según mercado |
| Email transaccional | ~$0.0001/mensaje |
| Almacenamiento R2 | 10 GB gratis; sin egress |
| Soporte | horas de la agencia |

### 7.3 Objetivos de negocio

- Margen bruto objetivo por tenant ≥ 70% a los 6 meses.
- Payback del setup < 3 meses de suscripción.
- ARPU creciente vía land-and-expand (web → WhatsApp → agentes).
- Churn mensual objetivo < 3%.

> **Pendiente de decisión**: precios finales en USD/MXN/BRL según mercado objetivo, tras validar costos reales en el MVP1 (medir `ai_runs` reales por tenant).

---

## 8. Riesgos del roadmap

| Riesgo | Mitigación |
|---|---|
| MVP1 muy grande (builder XL) | Limitar a template único sólido + componentes esenciales; personalización después |
| Calidad RAG deficiente | Evaluación por vertical + umbral + mejorar chunking |
| WhatsApp retrasa MVP2 | Prototipar con web primero; WhatsApp se aísla como épica |
| Costo IA alto por tenant | Routing + caching + presupuesto medido desde el día 1 |
| Un solo dev | Priorizar épicas por valor; delegar UI con templates/Filament; automatizar CI |
