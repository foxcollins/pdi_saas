# 01 — Visión

**AI Business Platform**
Plataforma SaaS de Presencia Digital Inteligente para PyMEs

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Estado**: Aprobada
- **Documento fuente**: `00-macro-idea.docx`

---

## 1. Declaración de visión

Convertir a cualquier empresa en una **entidad digital inteligente**: un Business Brain que conoce el negocio, conversa con los clientes, capta oportunidades y ejecuta acciones sobre sus canales y procesos.

La plataforma no es un "website builder con chatbot". Es una **plataforma empresarial de IA** que crea una identidad digital inteligente para cada empresa y conecta su web, conocimiento, clientes, canales y automatizaciones en un solo sistema.

La web es **una interfaz más** del producto. El producto real es el **Business Brain** de cada empresa.

### Arquitectura conceptual

```
                    ┌───────────────────┐
                    │      TENANT       │
                    │     EMPRESA       │
                    └─────────┬─────────┘
                              │
                    ┌─────────▼─────────┐
                    │   BUSINESS BRAIN  │
                    └─────────┬─────────┘
                              │
       ┌──────────────┬───────┼───────┬──────────────┐
       ▼              ▼       ▼       ▼              ▼
     WEBSITE        RAG     MEMORY   CRM          ANALYTICS
       │              │       │       │              │
       └──────────────┴───────┼───────┴──────────────┘
                              ▼
                       AI ORCHESTRATOR
                              │
                ┌─────────────┼─────────────┐
                ▼             ▼             ▼
             AGENTS         TOOLS       WORKFLOWS
                │             │             │
                └─────────────┼─────────────┘
                              ▼
                  ┌──────────────────────┐
                  │   EXTERNAL SYSTEMS   │
                  ├──────────────────────┤
                  │ WhatsApp              │
                  │ Instagram             │
                  │ Email                 │
                  │ Calendar              │
                  │ ERP                   │
                  │ CRM externo           │
                  │ Payments              │
                  │ APIs                  │
                  └──────────────────────┘
```

---

## 2. Misión del producto

Permitir que una empresa pase de tener **una página web** (un folleto digital estático) a tener **un sistema digital inteligente** de atención y operación, sin necesidad de programación.

- Informar sobre la empresa (web + conocimiento).
- Conversar con visitantes y clientes (asistente de IA).
- Captar y gestionar clientes (CRM conversacional, leads).
- Automatizar procesos (agentes, workflows, integraciones).
- Mantener continuidad omnicanal (web, WhatsApp, redes, email).

Todo bajo un modelo **multi-tenant**: miles de empresas, una plataforma.

---

## 3. Problema

El desarrollo web tradicional para PyMEs se reduce a: contratar una agencia, crear una landing corporativa, publicarla y dar el proyecto por terminado.

La página contiene información institucional, productos o servicios, misión/visión, un formulario, teléfono, WhatsApp y redes sociales.

**El problema**: la web funciona como un folleto digital. El visitante debe entrar, leer, buscar, encontrar un contacto, enviar un mensaje y esperar una respuesta humana. No hay conocimiento activo, no hay captación sistemática, no hay seguimiento y no hay ejecución.

Consecuencias para la empresa:
- Depende de la disponibilidad humana para atender.
- Pierde visitantes que no encuentran respuesta.
- No captura datos ni intenciones de compra.
- No da seguimiento a las oportunidades.
- No conecta su información (documentos, catálogos, políticas) con la atención.
- Cada mejora (más canales, CRM, automatización) es un nuevo proyecto costoso.

---

## 4. Propuesta de valor

Cada empresa debe tener una **presencia digital que conozca su negocio**, que pueda **conversar con sus clientes** y que sea capaz de **ejecutar acciones**.

La propuesta de valor se resume en:

> "Creamos para tu empresa una web profesional con una IA que conoce tu negocio, atiende a tus visitantes y puede conectarse con WhatsApp, tus clientes y tus procesos."

### Modelo land-and-expand (el camino comercial)

El cliente entra por una necesidad concreta (la web) y crece hacia toda la plataforma:

```
WEB
 ↓
AI CHAT
 ↓
RAG
 ↓
WHATSAPP
 ↓
CRM
 ↓
AUTOMATION
 ↓
AI AGENTS
 ↓
BUSINESS PLATFORM
```

### Beneficios por etapa

| Etapa | Beneficio para el cliente |
|---|---|
| Web | Presencia profesional con dominio propio y SEO |
| AI Chat | Atención inmediata 24/7 que conoce la empresa |
| RAG | Respuestas exactas basadas en su documentación autorizada |
| WhatsApp | El negocio atiende en el canal donde ya habla el cliente |
| CRM | Historial unificado, leads, intenciones y seguimiento |
| Automation | Tareas repetitivas ejecutadas automáticamente |
| AI Agents | Agentes especializados (ventas, soporte, agenda, postventa) |
| Business Platform | Una capa inteligente que conecta presencia, conocimiento, clientes y operación |

---

## 5. Principios

1. **IA-first**: la inteligencia no es un add-on; es el núcleo de la experiencia.
2. **El Business Brain es el producto**: la web es una interfaz, no el objetivo.
3. **Multi-tenant estricto**: cada empresa aislada; una sola plataforma.
4. **Core genérico, módulos y plugins**: el núcleo no se rompe al agregar verticales o integraciones.
5. **Knowledge-authorized**: el asistente responde desde el conocimiento que la empresa autoriza (menos alucinaciones).
6. **Memory-driven**: el sistema recuerda al cliente y mantiene continuidad de contexto.
7. **Omnicanal**: un solo cerebro, muchos canales (web, WhatsApp, Instagram, Facebook, email).
8. **De agente a acción**: el asistente no solo habla; ejecuta tareas con herramientas.
9. **Automatización por integración**: N8N y APIs para conectar sistemas externos sin tocar el core.
10. **Privacy by Design**: aislamiento, retención configurable, anonimización, consentimiento, trazabilidad.
11. **Modelo agnóstico de IA**: el core no se acopla a ningún proveedor de LLM.
12. **Wedge comercial**: resolver primero "web inteligente"; evolucionar a plataforma.
13. **Configurable antes que custom**: templates, componentes y configuración en vez de programación por cliente.
14. **Escalar sin multiplicar trabajo**: de 1 a 1.000 clientes sin crecer el trabajo técnico proporcionalmente.

---

## 6. Usuarios

### 6.1 Empresas (PyMEs)
Negocios que ya tienen o necesitan web, Instagram, Facebook, WhatsApp, correo, catálogo, documentación y/o personal de atención. La plataforma es horizontal: restaurantes, clínicas, inmobiliarias, industria, abogados, educación, comercio y servicios profesionales.

### 6.2 Agencias
El producto es la herramienta que una agencia (como FoxCode) usa para entregar cientos de proyectos de forma configurable y repetible: `Cliente → configuración → template → contenido → knowledge base → integraciones → deploy automático`.

### 6.3 Equipo interno / administradores de tenant
Usuarios de la empresa cliente que gestionan su presencia: contenido web, conocimiento, canales, conversaciones, leads y configuración.

### 6.4 Visitantes y clientes
Quienes interactúan con el sitio o los canales de la empresa: buscan información, preguntan, solicitan cotizaciones, reservan o compran.

### 6.5 Operadores humanos
Personas de la empresa que revisan, escalan o supervisan las conversaciones atendidas por la IA.

---

## 7. Actores

| Actor | Descripción |
|---|---|
| **Tenant Owner** | Empresa cliente; propietario de su tenant, dominio y datos |
| **Platform Admin** | Equipo de la plataforma (FoxCode); gestiona el sistema global |
| **Agency User** | Usuario de agencia que configura tenants para sus clientes |
| **Tenant Users** | Empleados de la empresa con roles y permisos (operadores, editores, admins) |
| **Business Brain** | El conjunto: conocimiento + memoria + agentes + herramientas + CRM del tenant |
| **AI Orchestrator** | Coordina modelos, agentes, herramientas y memoria |
| **Agents especializados** | Sales, Support, Reception, Booking, Follow-up, Post-sales |
| **Visitor / Customer** | Persona que interactúa a través de web o canales |
| **External Systems** | WhatsApp, Instagram, Facebook, Email, Calendar, ERP, CRM externo, Payments, APIs |

### Ciclo de vida de un actor: el cliente

```
Nuevo → Lead → Calificado → En negociación → Cliente → Recurrente → Referente
```

La IA alimenta cada transición con datos (intenciones, interés, cotizaciones, historial).

---

## 8. Casos de uso

### 8.1 Principales

| # | Caso de uso | Actores | Resultado |
|---|---|---|---|
| CU-01 | Crear una web profesional con dominio propio | Tenant Owner / Agency | Sitio publicado y operativo |
| CU-02 | Configurar la base de conocimiento (PDF, DOCX, URLs, FAQs, catálogos) | Tenant Owner | Conocimiento listo para la IA |
| CU-03 | Conversar con el asistente en la web | Visitor | Respuesta basada en el conocimiento autorizado |
| CU-04 | Captar un lead y unificarlo en el CRM | Visitor / IA | Lead con intención, puntaje y siguiente acción |
| CU-05 | Atender por WhatsApp con continuidad | Customer / IA | Conversación con contexto del historial |
| CU-06 | Generar una cotización | Customer / Sales Agent | Requisitos → catálogo → precio → PDF → lead → seguimiento |
| CU-07 | Automatizar un proceso externo | IA / N8N / ERP | Acción ejecutada en sistema externo |
| CU-08 | Analizar desempeño | Tenant Owner | Métricas y recomendaciones de la IA |

### 8.2 Flujo de una conversación inteligente

```
Usuario → detección de intención → recuperación de información relevante (RAG)
       → contexto (memoria) → LLM → respuesta
```

### 8.3 Flujo de IA con capacidad de acción

```
Pregunta → entender intención → consultar información → decidir
        → utilizar una herramienta → ejecutar acción → confirmar resultado
```

Ejemplo (cotización):
`requisitos → consulta de catálogo → cálculo de precio → generación de cotización → creación del lead → envío del PDF → creación del seguimiento`

### 8.4 Flujo de custom domain

```
Request → Host → Domain Resolver → Tenant → Website Configuration → Render
```

Miles de sitios con dominios y apariencias independientes sobre una misma infraestructura.

### 8.5 Pipeline de conocimiento

```
Documento → extracción → limpieza → chunking → embeddings → vector database
```

### 8.6 Flujo de integración

```
WhatsApp → AI Agent → Lead creado → N8N → CRM / Email / Calendar / Slack / ERP
```

---

## 9. Diferenciadores

1. **Intersección, no competencia**: el espacio diferencial está en la unión de Website + AI + Knowledge + Omnichannel + CRM + Automation + Agents. No se compite contra Wix, WordPress, Webflow ni un "builder con ChatGPT".
2. **Web inteligente desde el día 1**: la primera experiencia vendida ya incluye IA que conoce el negocio (no es un chat genérico).
3. **Knowledge-authorized RAG**: respuestas limitadas al conocimiento de la empresa, reduciendo inventos.
4. **Memoria de cliente + CRM conversacional**: un historial unificado por cliente a través de canales.
5. **Omnicanal con un solo cerebro**: continuidad de contexto web ↔ WhatsApp ↔ redes ↔ email.
6. **IA que ejecuta**: agentes con herramientas (cotizaciones, agenda, seguimiento, CRM) — no solo chat.
7. **Multi-tenant con dominio propio**: cada PyME se ve como un producto propio con su marca.
8. **Core genérico + plugins**: nuevas capacidades sin reescribir el sistema.
9. **Automatización abierta (N8N)**: el cliente conecta su propio ecosistema sin depender de nuestra lista de integraciones.
10. **Económico de operar**: Postgres+pgvector, modelos enrutados por costo, hosting eficiente.

---

## 10. Límites del producto

### 10.1 Qué NO se construye inicialmente

- Un CRM completo tipo Salesforce.
- Un ERP.
- Un ecommerce completo.
- Un constructor de sitios al nivel de Webflow.
- Decenas de integraciones.
- Múltiples agentes.
- Una app móvil.
- IA completamente autónoma.

### 10.2 El wedge inicial

> "Creo para tu empresa una web profesional con IA que conoce tu negocio y puede atender a tus visitantes."

Detrás de esa experiencia simple debe existir la arquitectura preparada para evolucionar hacia WhatsApp, CRM, agentes, automatizaciones y módulos adicionales.

### 10.3 Fuera de alcance (visión)

- No somos una agencia automatizada de diseño gráfico.
- No reemplazamos el juicio humano en decisiones sensibles (la IA propone; el humano autoriza cuando corresponde).
- No ejecutamos acciones destructivas sin control ni trazabilidad.

---

## 11. Visión a 3 años

### Roadmap por fases

| Fase | Nombre | Entregable |
|---|---|---|
| FASE 0 | Definición | Visión, multi-tenancy, modelo de datos, arquitectura |
| FASE 1 | AI Website | Builder, templates, custom domains, knowledge base, RAG, web chat |
| FASE 2 | Customer Intelligence | Conversaciones, CRM, memoria, analytics |
| FASE 3 | Omnichannel | WhatsApp, Instagram, Facebook, email |
| FASE 4 | AI Agents | Sales, Support, Booking, Follow-up |
| FASE 5 | Automation Platform | N8N, integraciones, tools, workflows |
| FASE 6 | Vertical Marketplace | Restaurantes, clínicas, inmobiliarias, industria, otros |

### Estados objetivo

- **Año 1**: MVP (AI Website) comercialmente vendible; primeros clientes de pago; pipelines de conocimiento y RAG funcionando.
- **Año 2**: CRM conversacional y WhatsApp; el producto ya es una plataforma, no solo un builder.
- **Año 3**: agentes y automatización; la empresa tiene una capa de inteligencia que conecta presencia digital con procesos internos (`Company → Digital Brain → Web/Channels/Employees → AI/Agents/Automation → CRM/ERP/APIs`).

---

## 12. Métricas North Star

### North Star

**Conversaciones inteligentes atendidas por tenant por mes** — mide el valor central: la IA atendiendo clientes reales con conocimiento.

### Métricas de apoyo

| Área | Métrica |
|---|---|
| Adopción | Tenant onboarding time (config → deploy → primera conversación) |
| Producto | % de conversaciones resueltas sin escalamiento humano |
| Crecimiento | Leads generados por mes; conversiones de visita a lead |
| Retención | Churn mensual; expansión de plan (land-and-expand) |
| IA | Preguntas sin respuesta; tasa de recuperación RAG; costo IA por conversación |
| Calidad | Precisión de respuestas con conocimiento (evaluación humana/supervisada) |

---

## 13. Hipótesis de negocio

### 13.1 Tesis del producto

> "Una plataforma SaaS que permite a cualquier empresa crear una presencia digital inteligente, conectada a su información, canales de comunicación y procesos empresariales, utilizando IA y automatización para atender clientes, generar oportunidades y ejecutar tareas."

### 13.2 Hipótesis por validar

1. **H1 — Demanda**: las PyMEs compran una "web con IA que conoce el negocio" como primer paso.
2. **H2 — Land-and-expand**: los clientes que entran por la web adoptan después WhatsApp, CRM y automatización (incremento de ARPU).
3. **H3 — Conocimiento autorizado**: un asistente basado en RAG genera confianza y conversión superior a un chatbot genérico.
4. **H4 — Aislamiento**: el modelo multi-tenant (una plataforma, dominio propio por cliente) reduce costos y habilita precios accesibles.
5. **H5 — Economía IA**: con routing por costo y caching, el costo IA por conversación es fraccional y recuperable vía consumo.

### 13.3 Modelo de ingresos

```
Setup inicial + suscripción mensual + consumo de IA + add-ons
```

| Plan | Incluye |
|---|---|
| Starter | Web, dominio e IA básica |
| Business | Web, IA, RAG, CRM y WhatsApp |
| Pro | Agentes, automatización y múltiples canales |
| Enterprise | Infraestructura dedicada, integraciones avanzadas y SLA |

Los precios definitivos se calcularán tras conocer costos de infraestructura, LLM, WhatsApp, almacenamiento, soporte y margen objetivo (ver `09-Business-Model`).

### 13.4 Indicador estratégico

No construir una agencia automatizada: construir el producto que una agencia usa para entregar cientos de proyectos. La agencia es un canal comercial; el flujo interno es configurable y repetible para reducir la dependencia de horas de programación por cliente.

---

## 14. Decisiones arquitectónicas derivadas

Estas decisiones se derivan de la visión y quedan fijadas como principios. El detalle técnico fino se desarrolla en `03-Domain-Multi-Tenancy.md` y `04-Architecture.md`.

### 14.1 Decisiones fijadas

1. **Backend monoestack**: Laravel (PHP) con frontend Inertia/Vue para la app y el builder; API y admin en el mismo lenguaje.
2. **Multi-tenancy: shared schema + Row-Level Security (RLS)**. Una sola base de datos con `tenant_id` por fila y políticas RLS; test de fuga cross-tenant obligatorio en CI desde el día 1.
3. **Custom domains por tenant**: cada empresa usa su dominio propio; el enrutamiento resuelve `Host → Tenant → Website Config → Render`.
4. **Base de datos única**: PostgreSQL 16 + **pgvector** para datos relacionales y vectores (RAG) en la misma instancia; Redis para cache y colas.
5. **Capa de abstracción de LLM**: el core no se acopla a ningún proveedor. Se expone una interfaz `AiProvider` con **routing por costo/calidad** (modelos baratos para alta escala, modelos de calidad para agentes). OpenRouter como gateway de acceso multi-proveedor.
6. **RAG empresarial**: pipeline documento → extracción → limpieza → chunking → embeddings → pgvector; recuperación con filtro por tenant.
7. **Memoria de cliente configurable**: resúmenes estructurados + políticas de retención/anonimización/eliminación (no retener todo indefinidamente).
8. **AI Orchestrator**: coordina modelos, agentes, herramientas y memoria; los agentes son configurables (instrucciones, herramientas, permisos, objetivos).
9. **N8N como capa de acción e integración**: conecta sistemas externos (WhatsApp, ERP, CRM, Calendar, Payments) sin implementar cada integración en el core.
10. **Core genérico + plugins**: AI, RAG, CRM, Website, Users, Domains, Analytics en el core; WhatsApp, Instagram, Calendar, Ecommerce, Payments, Booking y verticales como plugins.
11. **Deploy económico y robusto**: Docker Compose sobre un VPS (Hetzner) con Cloudflare delante (CDN, SSL, custom domains, R2 para storage); N8N self-hosted.
12. **Privacy by Design**: aislamiento por tenant, autorización por roles, cifrado en tránsito, gestión segura de secretos, auditoría, retención/eliminación, consentimiento y trazabilidad de acciones de agentes. Considerar LGPD y normas del mercado objetivo.

### 14.2 Implicaciones para el equipo

- Desarrollo local: Laragon (PHP, Postgres) + Docker para servicios (pgvector, N8N) cuando apliquen.
- El stack local se afina en `04-Architecture.md` sin alterar los principios aquí fijados.
- Cada decisión de stack futura debe evaluarse contra estos principios antes de integrarse al core.

---

## 15. Conclusión estratégica

La oportunidad no está en competir con Wix, WordPress, Webflow ni con un constructor de páginas con ChatGPT. Está en la **intersección** de Website + AI + Knowledge + Omnichannel + CRM + Automation + Agents.

El builder es la puerta de entrada. El activo estratégico es el **núcleo multi-tenant de conocimiento, memoria, agentes, herramientas e integración**.

La plataforma comienza resolviendo un problema sencillo y vendible —una web empresarial inteligente— pero se construye con una arquitectura que permite evolucionar hacia un **Business Brain** completo para cada empresa.
