# 02 — Product Requirements

**AI Business Platform** · Requisitos funcionales y no funcionales por módulo

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: `01-Vision.md`
- **Estado**: Borrador aprobado como referencia

---

## 1. Alcance

Este documento define los requisitos funcionales (RF) y no funcionales (RNF) de la plataforma, organizados por módulo y priorizados con MoSCoW para los entregables del MVP.

### Prioridad MoSCoW

- **M** — Must have (imprescindible en la fase correspondiente)
- **S** — Should have (deseable, se puede retrasar una iteración)
- **C** — Could have (nice to have)
- **W** — Won't have (por ahora, fuera de alcance)

---

## 2. Requisitos por módulo

### 2.1 Multi-tenant y autenticación

| ID | Requisito | Prioridad |
|---|---|---|
| RF-001 | Alta de tenants con datos de empresa (nombre, logo, industria, país) | M |
| RF-002 | Usuarios con roles (platform admin, agency, tenant owner, tenant editor, tenant operator) | M |
| RF-003 | Autenticación por email/contraseña con verificación | M |
| RF-004 | Recuperación de contraseña y 2FA | C |
| RF-005 | Cada tenant aislado lógicamente: toda query de datos de tenant pasa por RLS | M |
| RF-006 | Panel de administración de la plataforma (gestionar tenants, planes, incidencias) | M |
| RF-007 | Invitación de usuarios a un tenant con roles | S |
| RNF-001 | Toda ruta de datos de tenant (jobs, webhooks, comandos) mantiene contexto de tenant | M |

### 2.2 Website Builder

| ID | Requisito | Prioridad |
|---|---|---|
| RF-010 | Crear un sitio desde templates o en blanco | M |
| RF-011 | Componentes reutilizables: Hero, Navbar, Servicios, Productos, Equipo, Testimonios, Galería, FAQ, Mapa, Contacto, CTA, Blog, Chat IA | M |
| RF-012 | Configurar branding: logo, colores, tipografía | M |
| RF-013 | Edición de contenido (texto, imágenes, SEO por página) | M |
| RF-014 | Publicar / despublicar sitio con versión en borrador y en vivo | M |
| RF-015 | Múltiples páginas y navegación configurable | S |
| RF-016 | Editor responsive con previsualización en vivo | S |
| RF-017 | Blog con entradas gestionables | C |
| RF-018 | Clonar un sitio completo (duplicar template como base) | S |
| RNF-010 | El render del sitio público es rápido y SEO-friendly (SSR/servidor) | M |

### 2.3 Custom Domains

| ID | Requisito | Prioridad |
|---|---|---|
| RF-020 | Asignar un dominio personalizado a un tenant | M |
| RF-021 | Flujo de verificación de dominio (DNS TXT/CNAME) | M |
| RF-022 | Enrutamiento por Host: resolver dominio → tenant → configuración → render | M |
| RF-023 | SSL automático para el dominio personalizado | M |
| RF-024 | Dominio principal de la plataforma por defecto (*.plataforma.com) | M |
| RF-025 | Múltiples dominios por tenant con dominio principal | S |
| RNF-020 | La resolución de dominio no filtra datos de otros tenants | M |

### 2.4 Knowledge Base

| ID | Requisito | Prioridad |
|---|---|---|
| RF-030 | Subir documentos: PDF, DOCX, XLSX, PPTX | M |
| RF-031 | Ingestar URLs/sitios web como fuente de conocimiento | M |
| RF-032 | Crear FAQs manuales | M |
| RF-033 | Añadir texto manual (productos, servicios, políticas, manuales) | M |
| RF-034 | Listar, buscar, editar, eliminar y reprocesar documentos | M |
| RF-035 | Estado del procesamiento por documento (pendiente, procesando, listo, error) | M |
| RF-036 | Límites por plan (nº docs, tamaño, páginas) | S |
| RNF-030 | El pipeline de procesamiento es asíncrono (queue) y no bloquea la UI | M |

### 2.5 RAG empresarial

| ID | Requisito | Prioridad |
|---|---|---|
| RF-040 | Recuperación de conocimiento del propio tenant para responder | M |
| RF-041 | La recuperación filtra siempre por tenant_id | M |
| RF-042 | Soporte de citas o fuentes en la respuesta cuando corresponde | S |
| RF-043 | Modo "solo conocimiento" (no inventar) y modo "conversacional + conocimiento" | S |
| RF-044 | Umbral de confianza: si no hay información relevante, el asistente lo indica y deriva a contacto | M |
| RNF-040 | Latencia de respuesta objetivo < 3 s para consultas comunes | S |

### 2.6 Web AI Chat

| ID | Requisito | Prioridad |
|---|---|---|
| RF-050 | Widget de chat en el sitio público del tenant | M |
| RF-051 | Personalización del widget (colores, textos, posicionamiento) | S |
| RF-052 | Streaming de respuesta en tiempo real | M |
| RF-053 | Captura de datos de contacto del visitante para seguimiento | M |
| RF-054 | Registro de la conversación en el CRM si se identificó al visitante | M |
| RF-055 | Opción de derivar a un humano (formulario o enlace) | S |
| RNF-050 | El chat web funciona sin registrar cuenta previa del visitante | M |

### 2.7 CRM conversacional

| ID | Requisito | Prioridad |
|---|---|---|
| RF-060 | Historial unificado de conversaciones por contacto (web, WhatsApp, Instagram, email) | M |
| RF-061 | Contactos con datos, preferencias, intereses y estado comercial | M |
| RF-062 | La IA genera automáticamente: resumen, clasificación, intención, lead score y siguiente acción | S |
| RF-063 | Pipeline de leads (nuevo → calificado → negociación → cliente) | S |
| RF-064 | Notas y tareas manuales sobre contactos | S |
| RF-065 | Vista de conversaciones y bandeja de escalamiento humano | S |
| RNF-060 | Los datos de contacto cumplen políticas de retención configurables | M |

### 2.8 Memory

| ID | Requisito | Prioridad |
|---|---|---|
| RF-070 | Memoria por cliente: resúmenes estructurados de interacciones | S |
| RF-071 | Políticas de retención, archivado, anonimización y eliminación configurables | M |
| RF-072 | Consentimiento del usuario cuando la normativa lo exija (LGPD) | M |
| RNF-070 | No se conserva todo el texto indefinidamente; se priorizan resúmenes | M |

### 2.9 Analytics

| ID | Requisito | Prioridad |
|---|---|---|
| RF-080 | Métricas web tradicionales (visitas, páginas, origen) | S |
| RF-081 | Métricas IA: preguntas, intenciones, respuestas sin respuesta, conversaciones | M |
| RF-082 | Métricas comerciales: leads generados, conversiones, fuentes | M |
| RF-083 | Recomendaciones de la IA (ej.: muchos preguntan por precios → sugerir añadir a web/knowledge base) | C |
| RNF-080 | Los eventos de analítica se registran sin degradar la experiencia | S |

### 2.10 Automatización e integraciones

| ID | Requisito | Prioridad |
|---|---|---|
| RF-090 | Webhooks salientes para eventos del negocio (lead nuevo, conversación, etc.) | S |
| RF-091 | N8N como capa de acción/integración (webhook hacia y desde N8N) | S |
| RF-092 | WhatsApp (canal) | M (Fase 3) |
| RF-093 | Instagram / Facebook (canales) | S |
| RF-094 | Email (enviar y recibir, seguimiento de hilos) | S |
| RF-095 | Calendario / agenda (disponibilidad y reservas) | C |
| RNF-090 | Las integraciones nunca omiten el contexto de tenant | M |

### 2.11 Administración y billing

| ID | Requisito | Prioridad |
|---|---|---|
| RF-100 | Planes de suscripción por tenant y cambios de plan | S |
| RF-101 | Consumo de IA medido y facturable por tenant | S |
| RF-102 | Facturación recurrente (Stripe) | C |
| RF-103 | Dashboard de costos por tenant | C |
| RNF-100 | Precios finales pendientes de cálculo de unit economics (ver `09`) | S |

---

## 3. Requisitos no funcionales (transversales)

| ID | Requisito | Prioridad |
|---|---|---|
| RNF-200 | **Seguridad**: aislamiento por tenant, autorización por roles, cifrado en tránsito (TLS), gestión segura de secretos, auditoría de acciones | M |
| RNF-201 | **Privacidad**: Privacy by Design; retención, anonimización y eliminación configurables; consentimiento (LGPD y normativas del mercado) | M |
| RNF-202 | **Escalabilidad**: de 1 a 1.000+ tenants sin multiplicar trabajo técnico; consultas con índice por tenant_id | M |
| RNF-203 | **Rendimiento**: latencia objetivo chat < 3 s; render web rápido; jobs en cola (Redis) | S |
| RNF-204 | **Disponibilidad**: deploy con mínima o nula interrupción; backups automáticos de Postgres | M |
| RNF-205 | **Observabilidad**: logs estructurados, métricas, trazabilidad de acciones de agentes | S |
| RNF-206 | **Mantenibilidad**: core genérico + plugins; arquitectura de IA agnóstica de proveedor | M |
| RNF-207 | **Costo IA**: routing por tarea (baratos para alta escala, calidad para agentes), caching y batch | M |
| RNF-208 | **Trazabilidad**: registro de cada acción ejecutada por un agente (qué, cuándo, quién, resultado) | M |

---

## 4. Matriz de prioridades por fase

| Módulo | MVP1 (AI Website) | MVP2 (Customer Intelligence) | MVP3 (Agents + Omnichannel) |
|---|---|---|---|
| Multi-tenant / auth | M | M | M |
| Website Builder | M | M | M |
| Custom Domains | M | M | M |
| Knowledge Base | M | M | M |
| RAG | M | M | M |
| Web AI Chat | M | M | M |
| Analytics | — | M | M |
| CRM conversacional | — | M | M |
| Memory | — | S | M |
| WhatsApp | — | M | M |
| Automatización (N8N) | — | S | M |
| Agentes especializados | — | — | M |
| Email / Social | — | — | S |
| Billing recurrente | — | S | M |

---

## 5. Criterios de aceptación transversales

1. Un usuario de tenant A **nunca** puede leer/escribir datos de tenant B (verificado por test automatizado de fuga cross-tenant).
2. El asistente responde con conocimiento del tenant; ante falta de contexto, deriva a contacto (no inventa).
3. Todo documento de conocimiento tiene estado trazable y es eliminable por el tenant.
4. Las acciones de agentes quedan registradas con trazabilidad.
5. El sistema funciona bajo los límites de costo IA por plan definidos en `09`.
