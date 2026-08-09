# 08 — Integrations

**AI Business Platform** · WhatsApp, Social, Email, N8N y APIs

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: `03-Domain-Multi-Tenancy.md`, `04-Architecture.md`, `07-Agent-System.md`
- **Estado**: Referencia de diseño (Fase 3-5)

---

## 1. Principios

1. Todos los canales usan el **mismo núcleo** (orquestador, RAG, memoria, CRM).
2. Todo webhook entrante fija contexto de tenant antes de procesar.
3. Las credenciales de integraciones se **cifran** (`integrations.config_encrypted`).
4. Los canales son **plugins**; el core no depende de ninguno.
5. N8N es la capa de acción hacia sistemas externos, no un sustituto del núcleo.

---

## 2. WhatsApp — decisión pendiente

### 2.1 Opciones

| Opción | Pros | Contras |
|---|---|---|
| **A. WhatsApp Cloud API oficial (Meta)** | Oficial y estable; sin riesgo de ban; plantillas oficiales; precio por conversación (~$0.01-0.08 según mercado) | Requiere cuenta Meta Business, revisión de plantillas, números verificados |
| **B. BSP (proveedor de negocio)** | Gestión de plantillas y soporte | Coste por mensaje adicional |
| **C. Bridge no oficial (WPPConnect/Baileys)** | Gratis, sin costes Meta | **Riesgo de bloqueo**, ToS Meta, inestable → NO recomendado para producción |

### 2.2 Arquitectura (opción A)

```
WhatsApp (Meta) ──webhook──> Cloudflare → app /webhooks/whatsapp?tenant=<sig>
   → verificar firma → fijar tenant → orquestador → responder
   → respuesta vía Graph API (mensajes de sesión 24h / plantillas)
```

- **Conexión del número**: la app (o un worker/ngrok en dev) recibe webhooks; la respuesta usa la API de Graph.
- **Templates**: aprobación previa de plantillas para primeros mensajes (fuera de ventana 24h) y notificaciones.
- **Continuidad**: mapear `phone` → `contacts.phone` para unificar con web/email.

### 2.3 Costo

- Se mide por conversación (Meta) y se traslada al plan/consumo del tenant. Requiere revisión en `09`.

> **Pendiente de decisión**: A (oficial) vs C (bridge). **Recomendado: A** para producción; C solo para pruebas locales sin clientes reales.

---

## 3. Instagram y Facebook

- **Instagram**: Messenger API (Meta) para DM comerciales; requiere cuenta de negocio + revisión de permisos.
- **Facebook**: Messenger/Page API.
- Ambos pueden usar el mismo tipo de flujo webhook → orquestador → respuesta (mismo mensaje de marketing de plantillas no aplica igual; responder dentro de ventana 24h es gratuito).
- Fase 3+: implementación incremental (primero Instagram, luego Facebook).

---

## 4. Email

| Aspecto | Decisión por defecto |
|---|---|
| Envío | API transaccional (Resend / Postmark / Mailgun) vía `send_message` |
| Dominio del tenant | SPF/DKIM configurados para el dominio de la empresa |
| Recepción / inbound | Webhook de email → parsear → unir a conversación del contacto |
| Hilos | Continuidad por `thread-id` mapeado al contacto |

- Útil para: seguimiento de cotizaciones, notificaciones, postventa.

---

## 5. N8N (capa de automatización)

### 5.1 Rol

Conectar la plataforma con sistemas externos (ERP, CRM, Calendar, Payments, Slack, Google Sheets, etc.) **sin implementar cada integración en el core**.

### 5.2 Patrón de uso

```
Plataforma ──webhook──> N8N ──> sistema externo
Sistema externo ──webhook──> N8N ──> Plataforma (con contexto de tenant)
```

- **Hacia N8N**: la plataforma dispara `webhook_outbox` (eventos: `lead.created`, `conversation.closed`, `quote.generated`, `agent.action`).
- **Desde N8N**: N8N llama a la API de la plataforma con un token de integración por tenant (nunca credenciales globales de la app).

### 5.3 Gobernanza

- Cada tenant tiene su `integration` N8N (URL + secret) y sus workflows.
- El webhook del plugin valida firma/secret y fija `tenant_id` antes de procesar.
- Rate limits y reintentos con backoff en `webhook_outbox`.

---

## 6. Otras integraciones

| Sistema | Enfoque | Estado |
|---|---|---|
| Google Calendar / agenda | API de Calendar vía Booking Agent | Fase 4 |
| Payments (Stripe) | Cashier para billing de la plataforma; enlaces de pago para el tenant | Fase 5 |
| ERP / CRM externo | Vía N8N + webhooks | Fase 5 |
| Slack | Notificación a operadores vía N8N | Opcional |

---

## 7. Webhooks (diseño)

### 7.1 Entrantes (a la plataforma)

- Endpoint público por canal: `/webhooks/{channel}` con verificación de firma (HMAC/secret cifrado por tenant).
- Antes de tocar datos: resolver tenant y `set_config('app.tenant_id', ...)`.
- Idempotencia por `X-Webhook-Id` / hash de payload.

### 7.2 Salientes (`webhook_outbox`)

- Eventos con retry exponencial (1, 5, 30 min…) y dead-letter para inspección.
- Payload mínimo y `tenant_id` explícito.

---

## 8. Seguridad de integraciones

- Secretos cifrados con clave de la app (Laravel `Crypt` / KMS).
- Tokens de integración por tenant, rotables.
- Auditoría de cada llamada saliente/entrante.
- Revisión de permisos de Meta (scopes mínimos).
- Cumplimiento LGPD en datos de clientes que cruzan canales (consentimiento y finalidad).

---

## 9. Pendientes de decisión

- [ ] **WhatsApp: canal oficial (Meta) vs bridge** → recomendado: oficial (A).
- [ ] Proveedor de email transaccional (Resend / Postmark / Mailgun).
- [ ] Si el público objetivo es Brasil, revisar requisitos Meta para números + LGPD y WhatsApp Business API en BRL.
