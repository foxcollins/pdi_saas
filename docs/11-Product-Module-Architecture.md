# 11 — Product & Module Architecture

**AI Business Platform** · Arquitectura de producto: módulos, responsabilidades y principios arquitectónicos

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: `01-Vision.md`, `02-Product-Requirements.md`, `03-Domain-Multi-Tenancy.md`, `04-Architecture.md`
- **Estado**: Referencia aprobada (arquitectura de producto)
- **Origen**: consolidación de la especificación fundacional `AI Business Platform.md` (v0.1) con la serie `docs/`.

---

## 1. Definición de producto

El producto es una **plataforma SaaS multi-tenant para crear y operar presencias digitales inteligentes para empresas**.

No debe entenderse como un simple website builder, chatbot, CRM o plataforma de ecommerce. La plataforma combina:

- Sitios web de negocio
- Asistentes de IA
- Bases de conocimiento
- RAG
- Conversaciones con clientes
- CRM
- Comunicación omnicanal
- Ecommerce
- Agentes de IA
- Automatización
- Integraciones externas
- Analítica

El concepto fundamental:

> **Cada negocio tiene su propio Business Brain digital, mientras la plataforma SaaS provee la infraestructura, interfaces, capacidades de IA y módulos operativos a su alrededor.**

El sitio web es la puerta de entrada principal, pero **no es la abstracción central** del negocio.

---

## 2. Concepto central del producto

Cada cliente se representa como un **Tenant**. Un tenant es un negocio independiente.

```text
Plataforma
│
├── Tenant A
│   ├── Website
│   ├── Business Profile
│   ├── Knowledge Base
│   ├── AI
│   ├── CRM
│   └── Integraciones
│
├── Tenant B
│   ├── Website
│   ├── Business Profile
│   ├── Knowledge Base
│   ├── AI
│   ├── Store
│   └── WhatsApp
│
└── Tenant C
    ├── Website
    ├── Business Profile
    ├── Knowledge Base
    ├── AI
    ├── CRM
    └── Automatización
```

Todos los tenants comparten la infraestructura SaaS, pero sus datos, configuración, usuarios, dominios, conocimiento y operación de negocio deben permanecer **aislados lógicamente**.

---

## 3. Arquitectura de producto

La plataforma se organiza en cuatro capas conceptuales:

```text
                    PLATFORM
                       │
                       ▼
                TENANT / BUSINESS
                       │
                       ▼
                 BUSINESS BRAIN
                       │
       ┌───────────────┼────────────────┐
       ▼               ▼                ▼
    CHANNELS         MODULES          AI SYSTEM
       │               │                │
       └───────────────┼────────────────┘
                       ▼
                  AUTOMATION
                       │
                       ▼
               EXTERNAL SYSTEMS
```

El principio rector: **todos los módulos operan alrededor del mismo contexto de tenant y de negocio.**

---

## 4. Clasificación de módulos

### Módulos Core

Requeridos por casi todos los tenants.

```text
Core
├── Tenant
├── Usuarios y autenticación
├── Business Profile
├── Website
├── Domains
├── AI
├── Knowledge Base
├── RAG
└── Analytics
```

### Módulos de negocio

Capacidades opcionales activables por tenant.

```text
Business Modules
├── CRM
├── Conversaciones
├── Store
├── Órdenes
├── Booking
├── Catálogo
└── Gestión de clientes
```

### Módulos de canal

Canales de comunicación externos.

```text
Channels
├── Chat web
├── WhatsApp
├── Instagram
├── Facebook
├── Email
├── Telegram
└── Voz
```

### Módulos de inteligencia

```text
AI
├── Capa de proveedores de IA
├── AI Router
├── Agentes
├── Memoria
├── RAG
├── Tool Calling
└── Analítica de IA
```

### Módulos de automatización

```text
Automation
├── N8N
├── Webhooks
├── Workflows
├── Eventos
└── APIs externas
```

---

## 5. Módulo Tenant

El módulo Tenant representa la organización cliente.

Responsabilidades:

- creación de tenant;
- identificación de tenant;
- configuración de tenant;
- estado de tenant;
- relación con suscripción;
- activación de features;
- aislamiento de datos;
- seguimiento de uso.

Todo recurso propiedad de un tenant debe tener una relación clara con su tenant.

```text
Tenant
│
├── Usuarios
├── Dominios
├── Websites
├── Páginas
├── Productos
├── Clientes
├── Conversaciones
├── Conocimiento
├── Agentes
├── Integraciones
└── Workflows
```

Ningún tenant puede acceder a los recursos de otro.

---

## 6. Business Profile

El Business Profile representa la **identidad estructurada** de la empresa. Es uno de los objetos más importantes del sistema.

```text
Business Profile
├── company_name
├── legal_name
├── description
├── industry
├── services
├── products
├── locations
├── phone
├── email
├── social_links
├── opening_hours
├── company_values
├── certifications
└── business_metadata
```

Debe ser **reutilizable por otros módulos**: lo usa el website, la IA, el CRM, los agentes y el Store. El objetivo es evitar duplicar información del negocio en sistemas separados.

> Decisión fijada en `10-Implementation.md`: el Business Profile es la fuente única que hidrata web, RAG, chat, CRM y agentes.

---

## 7. Módulo Website

Gestiona la presencia digital pública del tenant.

Debe soportar:

- templates;
- páginas;
- secciones;
- componentes;
- temas;
- navegación;
- SEO;
- assets;
- publicación;
- preview;
- versionado;
- custom domains.

El website se genera desde **configuración estructurada**, no desde HTML arbitrario generado por IA.

```text
Website Configuration
        │
        ▼
Template
        │
        ▼
Sections
        │
        ▼
Components
        │
        ▼
Design System
        │
        ▼
Renderer
        │
        ▼
Public Website
```

---

## 8. Design System

La plataforma mantiene un design system controlado:

```text
Colores
Tipografía
Espaciado
Bordes
Radio
Sombras
Botones
Formularios
Tarjetas
Animaciones
Iconos
```

Garantiza consistencia visual. Un tenant puede personalizar su identidad visual **sin cambiar la arquitectura subyacente de componentes**.

---

## 9. Templates de website

Los templates definen estructuras de alto nivel:

```text
Corporativo
Industrial
Tecnología
Restaurante
Servicios profesionales
Inmobiliaria
Médico
SaaS
Ecommerce
Portfolio
```

Los templates **no contienen datos del tenant**: solo proveen estructura.

```text
Template Industrial

Hero
Servicios
Productos
Industrias
Proyectos
Certificaciones
FAQ
CTA
Contacto
```

El tenant aporta el contenido.

---

## 10. Sistema de secciones y componentes

Las páginas se componen de secciones reutilizables.

```text
Page
│
├── Hero
├── Servicios
├── Productos
├── Estadísticas
├── Proyectos
├── Testimonios
├── FAQ
├── CTA
└── Contacto
```

Cada sección puede tener múltiples variantes.

```text
Hero
├── centered
├── split
├── image
├── video
├── fullscreen
└── minimal
```

Se favorecen componentes reutilizables sobre HTML arbitrario.

---

## 11. Generación de website con IA

La IA puede generar la configuración del sitio, pero **no debe generar HTML de producción sin control**.

```text
Business Information
        │
        ▼
AI Website Generator
        │
        ▼
Site Configuration
        │
        ├── Template
        ├── Theme
        ├── Sections
        ├── Variants
        └── Content
        │
        ▼
Website Renderer
```

La IA selecciona y configura componentes. La librería de componentes los renderiza. Esto garantiza consistencia, mantenibilidad, responsividad y seguridad.

---

## 12. Custom Domains

Cada tenant puede conectar uno o más dominios según su suscripción.

```text
tenant-a.com
tenant-b.com
tenant-c.com
```

Las peticiones entrantes se resuelven al tenant correcto.

```text
HTTP Request
     │
     ▼
Domain
     │
     ▼
Domain Resolver
     │
     ▼
Tenant
     │
     ▼
Website Configuration
     │
     ▼
Render
```

El dominio público **no debe exponer el identificador interno del tenant**.

> Implementado en `03 §4`: `DomainResolver` + verificación TXT real (DoH + fallback nativo) en cola.

---

## 13. Knowledge Base

Contiene información de negocio consumible por la IA.

Fuentes soportadas:

- PDF;
- DOCX;
- XLSX;
- PPTX;
- URLs;
- páginas del website;
- FAQs;
- documentación de producto;
- manuales;
- políticas;
- catálogos.

Pipeline:

```text
Source
  │
  ▼
Extraction
  │
  ▼
Normalization
  │
  ▼
Chunking
  │
  ▼
Embeddings
  │
  ▼
Vector Storage
```

---

## 14. Módulo RAG

Provee conocimiento contextual del negocio a los modelos de IA.

```text
User Question
      │
      ▼
Intent / Query Processing
      │
      ▼
Retrieval
      │
      ▼
Relevant Chunks
      │
      ▼
Context Builder
      │
      ▼
LLM
      │
      ▼
Response
```

El sistema recupera solo información relevante en vez de enviar toda la base de conocimiento al modelo.

**La capa RAG es tenant-aware**: una consulta del Tenant A nunca debe recuperar conocimiento del Tenant B.

---

## 15. Capa de proveedores de IA

La aplicación no depende directamente de un único proveedor de IA. Se expone una abstracción interna:

```text
AiProvider
    │
    ▼
Provider Router
    │
    ├── Groq
    ├── OpenAI
    ├── Gemini
    ├── Anthropic
    └── Otros proveedores
```

**Decisión fijada (multi-proveedor siempre)**: el core se conecta a través de **OpenRouter como gateway multi-proveedor**, con routing por costo/calidad (modelos baratos para alta escala, modelos de calidad para agentes). El proveedor inicial puede ser **Groq por su tier gratuito mientras sea viable**, pero la arquitectura nunca se acopla a un proveedor único (ver `01 §14.1` y `04 §3`).

---

## 16. Estrategia de costo de IA

La plataforma soporta **routing de modelos**. Tareas simples usan modelos baratos/rápidos; tareas complejas, modelos más capaces.

```text
Simple
├── FAQ
├── Clasificación
└── Extracción

Normal
├── Soporte al cliente
├── Recomendación de producto
└── Generación de contenido

Compleja
├── Análisis de negocio
├── Razonamiento complejo de agentes
└── Generación de propuestas
```

Esto minimiza el costo de IA conforme la plataforma escala.

---

## 17. Módulo de agentes de IA

Los agentes son trabajadores de IA capaces de razonar y usar herramientas.

```text
Agent
├── Identidad
├── Instrucciones
├── Objetivos
├── Conocimiento
├── Memoria
├── Tools
├── Permisos
└── Modelo
```

Agentes posibles:

```text
Sales Agent
Support Agent
Reception Agent
Booking Agent
Follow-up Agent
Post-Sales Agent
```

> Ver `07-Agent-System.md` para el detalle del AI Orchestrator, el loop de tools y la trazabilidad.

---

## 18. Sistema de herramientas (Tools)

Los agentes no ejecutan operaciones arbitrarias de la aplicación: usan **herramientas explícitas**.

```text
Agent
 │
 ├── search_products()
 ├── get_customer()
 ├── create_lead()
 ├── create_quote()
 ├── create_booking()
 ├── send_message()
 └── create_order()
```

Cada tool tiene:

- schema de entrada;
- validación;
- autorización;
- contexto de tenant;
- ejecución;
- resultado.

Esto crea un **límite controlado entre el razonamiento de la IA y las operaciones de negocio**.

---

## 19. Módulo CRM

Gestiona relaciones con clientes y leads.

```text
Customer
Lead
Company
Opportunity
Interaction
Task
Note
```

El CRM se integra con las conversaciones.

```text
Conversation
     │
     ▼
Customer
     │
     ▼
Lead
     │
     ▼
Opportunity
```

---

## 20. Módulo Conversaciones

Almacena interacciones con clientes con un **modelo unificado de conversación** independiente del canal.

```text
Conversation
├── Customer
├── Tenant
├── Channel
├── Messages
├── AI summaries
├── Intent
├── Status
└── Metadata
```

Canales posibles:

```text
Website
WhatsApp
Instagram
Facebook
Email
Telegram
Voz
```

La retención de datos debe ser configurable: borrado, archivado y políticas de retención.

---

## 21. Store / Módulo de comercio

El comercio es un **módulo opcional**, no una aplicación separada. Opera dentro del Business Brain del tenant.

```text
Store
├── Products
├── Categories
├── Variants
├── Inventory
├── Cart
├── Orders
├── Customers
└── Payments
```

El MVP de Store debe ser intencionalmente limitado:

**V1 recomendado:**

```text
Productos
Categorías
Páginas de producto
Cart
WhatsApp Checkout
```

**Posterior:**

```text
Pagos
Órdenes
Inventario
Envíos
Cupones
Pricing B2B
```

---

## 22. IA aplicada al comercio

El Store expone información de producto a la IA.

```text
Customer
    │
    ▼
AI Agent
    │
    ▼
Product Search
    │
    ▼
Inventory / Catalog
    │
    ▼
RAG / Product Knowledge
    │
    ▼
Recommendation
```

El agente puede llegar a realizar: recomendaciones, comparativas, calificación técnica, generación de cotizaciones, creación de carrito y de órdenes. **La IA nunca omite la validación de negocio ni los controles de pago.**

---

## 23. Arquitectura de canales

Los canales son **adaptadores alrededor de un modelo de conversación común**.

```text
WhatsApp Adapter ─┐
Instagram Adapter ├──► Conversation Service
Web Chat Adapter ─┤
Email Adapter ────┤
Telegram Adapter ─┘
```

El sistema de IA no necesita saber si el mensaje vino de WhatsApp o Instagram: recibe un **mensaje interno normalizado**.

---

## 24. Módulo de automatización

N8N opera como **capa externa de automatización/orquestación**. La plataforma expone eventos y webhooks.

```text
Lead Created
      │
      ▼
Webhook
      │
      ▼
N8N
      │
 ┌────┼────┐
 ▼    ▼    ▼
CRM Email Slack
```

Eventos posibles:

```text
lead.created
conversation.created
conversation.closed
order.created
payment.completed
booking.created
customer.created
document.processed
```

---

## 25. Arquitectura de integraciones

Las integraciones externas usan **adaptadores**.

```text
Integration Layer
│
├── WhatsApp
├── Instagram
├── Facebook
├── Google
├── Calendar
├── Payment Providers
├── ERP
├── CRM
└── N8N
```

La lógica de negocio del core **no depende directamente de APIs externas**.

---

## 26. Módulo Analytics

Analítica en múltiples niveles.

### Website analytics

- visitas;
- sesiones;
- páginas;
- conversiones.

### Conversation analytics

- conversaciones;
- intenciones;
- tiempo de respuesta;
- resolución por IA;
- leads generados.

### Business analytics

- leads;
- oportunidades;
- órdenes;
- ingresos;
- conversión.

### AI analytics

- tokens;
- requests;
- uso de modelos;
- latencia;
- fallos;
- estimación de costo.

---

## 27. Activación de features

No todo tenant tiene todos los módulos. La plataforma soporta **activación por feature**.

```text
Tenant A
features:
  website = true
  ai = true
  rag = true
  crm = false
  store = false
  whatsapp = false
```

```text
Tenant B
features:
  website = true
  ai = true
  rag = true
  crm = true
  store = true
  whatsapp = true
```

Este sistema feature/módulo es **fundamental para el modelo de negocio SaaS**.

---

## 28. Arquitectura de suscripción

Las suscripciones determinan qué features están disponibles.

```text
Plan
 │
 ├── Features
 ├── Limits
 ├── AI Usage
 ├── Channels
 └── Storage
```

```text
Starter
├── Website
├── AI Chat
└── RAG básico

Business
├── Starter
├── CRM
├── WhatsApp
└── Conversaciones

Pro
├── Business
├── Agentes
├── Automatización
└── Store
```

Los planes **no se hardcodean** a lo largo de la aplicación: el acceso a features usa un **sistema de entitlements centralizado**.

---

## 29. Aislamiento de datos

Toda operación propiedad de un tenant debe aplicar contexto de tenant.

```text
Request
   │
   ▼
Authenticated User
   │
   ▼
Tenant Context
   │
   ▼
Authorization
   │
   ▼
Business Operation
```

El aislamiento de tenant es **invariante obligatorio**. Ningún controller, servicio, repository, job o tool de IA puede acceder a recursos de tenant sin contexto explícito. **Los jobs en background también llevan contexto de tenant.**

> Implementado en `03 §6`: rutas de ejecución que nunca omiten tenant (HTTP, jobs, webhooks, comandos).

---

## 30. Aislamiento de IA por tenant

Las operaciones de IA requieren aislamiento adicional. Toda request de IA debe conocer:

```text
tenant_id
user_id
conversation_id
agent_id
```

- El retrieval de RAG siempre filtra por tenant.
- La memoria siempre filtra por tenant.
- El historial de conversación siempre filtra por tenant.
- Las tools ejecutan bajo la autorización del tenant.

Esto previene la fuga de información cross-tenant.

---

## 31. Alcance del MVP inicial

El primer MVP orientado a producción debe contener solo:

```text
CORE
├── Multi-Tenancy
├── Autenticación
├── Business Profile
├── Website Builder
├── Templates
├── Design System
├── Custom Domains
├── Knowledge Base
├── RAG
├── AI Web Chat
└── Analytics básico
```

El objetivo es demostrar:

> Un negocio puede obtener una web profesional que entiende su negocio e interactúa inteligentemente con sus visitantes.

---

## 32. MVP Fase 2

Tras validar el producto inicial:

```text
CRM
Conversaciones
WhatsApp
Gestión de leads
N8N
Automatizaciones básicas
```

---

## 33. MVP Fase 3

```text
Agentes de IA
Tools
Catálogo de productos
Store
Cotizaciones
Booking
Follow-up
Canales sociales
```

---

## 34. Plataforma a largo plazo

La plataforma final debe evolucionar hacia:

```text
                         BUSINESS
                             │
                             ▼
                     DIGITAL BUSINESS BRAIN
                             │
       ┌─────────────────────┼─────────────────────┐
       ▼                     ▼                     ▼
    WEBSITE              CHANNELS              INTERNAL
       │                     │                  SYSTEMS
       ▼                     ▼                     ▼
      AI                 AI AGENTS             AUTOMATION
       │                     │                     │
       └─────────────────────┼─────────────────────┘
                             ▼
                      BUSINESS OPERATIONS
```

La plataforma debe convertirse en una **capa operativa digital para PyMEs**, no solo una plataforma de websites.

---

## 35. Principios arquitectónicos

1. **Multi-tenancy primero**: diseñar como multi-tenant desde el inicio.
2. **Arquitectura modular**: las capacidades opcionales son módulos, no forks del producto.
3. **IA como infraestructura**: la IA es una capacidad de plataforma disponible para distintos módulos.
4. **Abstracción de proveedor**: ninguna lógica de negocio se acopla a un único proveedor de LLM.
5. **Datos estructurados sobre HTML generado**: la IA genera configuración estructurada; el renderer genera la UI de producción.
6. **Tools sobre acciones libres de IA**: los agentes ejecutan operaciones de negocio mediante tools explícitas y validadas.
7. **Business Profile como fuente de verdad**: la información del negocio se centraliza y reutiliza.
8. **RAG tenant-scoped**: el retrieval siempre respeta los límites del tenant.
9. **Automatización mediante eventos**: los eventos de negocio se exponen por webhooks/eventos para que N8N y sistemas externos reaccionen.
10. **Arquitectura awareness de suscripción**: los features se controlan por entitlements, no por checks de plan dispersos.
11. **Integraciones API-first**: los servicios externos se acceden mediante adaptadores.
12. **Escalar por configuración**: sumar un cliente es principalmente configuración, no desarrollo a medida.

---

## 36. Stack tecnológico recomendado

```text
Frontend
Laravel + Inertia + Vue 3

Backend
Laravel

Base de datos
PostgreSQL

Vector search
pgvector

Cache / Queue
Redis

Storage
S3-compatible / Cloudflare R2

Automatización
N8N

IA
Abstracción de proveedor (AiProvider)
Gateway multi-proveedor: OpenRouter (proveedor inicial operativo: Groq, tier gratuito)

Infraestructura
Docker Compose (VPS Hetzner)

Edge / DNS
Cloudflare

Autenticación
Basada en Laravel
```

> El proveedor de infraestructura debe seguir siendo reemplazable. Stack detallado en `04-Architecture.md`.

---

## 37. Estrategia de evolución del producto

```text
Website
   ↓
AI Website
   ↓
AI + Knowledge
   ↓
AI + Conversaciones
   ↓
AI + CRM
   ↓
AI + Omnicanal
   ↓
AI Agents
   ↓
Automatización
   ↓
Comercio
   ↓
Capa operativa digital
```

Cada fase aumenta el valor extraído del mismo tenant y Business Brain.

---

## 38. Posicionamiento estratégico

El producto no debe posicionarse como:

> "Un website builder con IA."

Debe posicionarse como:

> **"Una plataforma digital inteligente para empresas."**

La web es la puerta de entrada. El **Business Brain** es la abstracción central. La IA, CRM, conversaciones, comercio y automatización son capacidades alrededor de ese núcleo.

---

## 39. Regla de oro para agentes de desarrollo

Cualquier agente de desarrollo que trabaje en este proyecto debe entender:

> **No construir features aisladas. Construir capacidades reutilizables que operen dentro de la arquitectura de tenant y Business Brain.**

Antes de implementar una feature, el agente debe determinar:

1. ¿Qué módulo es dueño de la feature?
2. ¿La feature es tenant-scoped?
3. ¿Requiere una nueva entidad de dominio?
4. ¿Puede reutilizarla otro módulo?
5. ¿Requiere un evento?
6. ¿Requiere una capacidad de IA?
7. ¿Requiere una tool?
8. ¿Requiere una integración externa?
9. ¿Depende de un entitlement de suscripción?
10. ¿Preserva el aislamiento de tenant?
11. ¿Escala a miles de tenants?
12. ¿Puede habilitarse/deshabilitarse de forma independiente?

Ninguna feature debe implementarse como customización de un solo cliente si razonablemente puede ser una capacidad reutilizable de la plataforma.

---

## 40. Modelo mental final

```text
                         SAAS PLATFORM
                              │
                    ┌─────────▼─────────┐
                    │      TENANT       │
                    │      BUSINESS     │
                    └─────────┬─────────┘
                              │
                    ┌─────────▼─────────┐
                    │  BUSINESS PROFILE │
                    └─────────┬─────────┘
                              │
                    ┌─────────▼─────────┐
                    │   BUSINESS BRAIN  │
                    └─────────┬─────────┘
                              │
       ┌──────────────────────┼──────────────────────┐
       ▼                      ▼                      ▼
    WEBSITE                 AI SYSTEM              CRM
       │                      │                      │
       │              ┌───────┼───────┐              │
       │              ▼       ▼       ▼              │
       │             RAG    MEMORY  AGENTS           │
       │                      │       │              │
       └──────────────────────┼───────┘              │
                              │                      │
                       ┌──────▼──────┐               │
                       │    TOOLS    │               │
                       └──────┬──────┘               │
                              │                      │
              ┌───────────────┼──────────────────────┐
              ▼               ▼                      ▼
           WHATSAPP         STORE                 N8N
              │               │                      │
              └───────────────┼──────────────────────┘
                              ▼
                       EXTERNAL SYSTEMS
```

La plataforma siempre debe desarrollarse hacia esta arquitectura.

**La web atrae al cliente. El Business Brain retiene al cliente. La IA crea inteligencia. Los agentes ejecutan acciones. Los módulos generan valor recurrente. La arquitectura multi-tenant hace escalable el negocio.**
