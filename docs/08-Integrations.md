# 08 — Integrations

**AI Business Platform** · WhatsApp, Social, Email, N8N y APIs

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: `03-Domain-Multi-Tenancy.md`, `04-Architecture.md`, `07-Agent-System.md`
- **Estado**: Referencia de diseño (Fase 3-5); arquitectura de canales (drivers) definida

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

### 2.3 Modelo con clientes reales (multi-tenant)

Cada tenant tiene **su propio número y su propia WABA**; la plataforma actúa como **BSP / Solution Partner** y opera la mensajería por el cliente.

```
EMBARQUE (una vez por cliente)
  → el cliente crea/acepta su WABA (Embedded Signup) → la plataforma obtiene
    phone_number_id + access_token + waba_id (cifrados en `integrations`)

RUNTIME
  → el payload del webhook incluye metadata.phone_number_id
  → se resuelve a qué tenant pertenece → set_config('app.tenant_id') → RLS
  → ChatService responde → Graph API /messages del número del tenant

FUERA DE VENTANA 24H
  → se envía plantilla aprobada por Meta (primer contacto / notificaciones)
```

- **Número sandbox**: Meta entrega 1 número de prueba gratis por app (solo testing; solo puede escribir a números de test). **No es para producción.**
- **Coste**: por conversación de Meta (~$0.01-0.08), se traslada al plan del tenant.

### 2.4 Embarque — Embedded Signup (opción recomendada)

**Requisitos de la plataforma (se configuran una vez):**

| Requisito | Detalle |
|---|---|
| Cuenta de desarrollador | Meta for Developers |
| App tipo "Business" | Crear App → Business (no Consumer) |
| Producto WhatsApp | Añadido al app dashboard |
| Business Verification ⚠️ | **Obligatoria para producción**; tarda ~2-5 días (nombre legal, documento, web, BM ID) |
| Webhook configurado | URL pública HTTPS + verify token |
| Configurar Embedded Signup | App Dashboard → WhatsApp → redirect URI + dominio de la app |

**Requisitos por cliente (los completa en el popup de Meta, sin que la plataforma maneje datos sensibles):**

| Requisito | Detalle |
|---|---|
| Cuenta de Facebook | Para iniciar sesión en el popup |
| Business Manager | Se crea dentro del flujo; **no requiere tarjeta de pago** |
| Número de teléfono | Nuevo (compra/porta) o migrar el actual; no puede estar en otra WABA |
| Verificación del número | SMS/llamada con código durante el flujo |
| Nombre de negocio (display name) | Pasa revisión de Meta (no genérico) |
| Aceptar términos | WhatsApp Business en el popup |

Al terminar, la plataforma recibe un **código de autorización** en su `redirect URI` → lo canjea por `phone_number_id`, `access_token` (permanente) y `waba_id`.

---

## 3. Arquitectura común de canales (drivers)

Todos los canales usan el **mismo núcleo** (orquestador, RAG, memoria, CRM) y el **mismo patrón**: webhook entrante → resolver tenant → driver traduce evento → `ChatService::respond` → driver responde por su API.

```
Integrations (tabla existente)
  channel: whatsapp | instagram | facebook | telegram | email
  config_encrypted: { token | page_id | phone_number_id ... }
  status: connected / verified / active
  webhook_secret: firma HMAC por tenant

Webhook entrante → ChannelDriverInterface (patrón como AiProvider)
  ├─ WhatsAppDriver  (Meta Cloud API)
  ├─ MessengerDriver (IG / FB)
  └─ TelegramDriver  (Bot API)

Cada driver traduce "evento" → {mensaje, contacto} → ChatService
La respuesta saliente la hace el mismo driver por su API
```

- El límite `channels` del plan (ver `11`/E12) controla cuántos canales puede activar cada tenant.
- Cada `conversation` lleva `channel` + `external_channel_id` (id del canal) + `tenant_id` (RLS).

### 3.1 Comparativa de canales

| Aspecto | WhatsApp | IG/FB Messenger | Telegram |
|---|---|---|---|
| API | Cloud API (WABA) | Messenger API | Bot API |
| Identificador del contacto | Teléfono | PSID / instagram id | chat_id |
| Ventana de respuesta | 24h (luego plantilla) | 24h (luego marketing) | **Sin límite** |
| Revisión de negocio | Sí (estricta) | Sí (permisos de página) | **No** |
| Coste | Por conversación | Por conversación (no siempre) | **Gratis** |
| Embarque | WABA + número + verificación | Login + elegir página | Crear bot + token |
| Setup del cliente | Días (revisión) | Horas | **Minutos** |
| Unificación CRM | Por teléfono | Por PSID (débil) | Por username/chat_id |

---

## 4. Instagram y Facebook Messenger (Meta)

- **Instagram**: Messenger API (Meta) para DM comerciales; requiere cuenta de negocio + revisión de permisos.
- **Facebook**: Messenger/Page API.
- Ambos usan el mismo flujo webhook → orquestador → respuesta; responder dentro de la ventana 24h es gratuito.

### 4.1 Flujo

```
1. REQUISITOS
   ├─ Facebook Page del negocio
   ├─ Instagram profesional conectado a la página (o Instagram API)
   ├─ App de Meta con productos Messenger + Instagram
   └─ Permisos revisados: pages_messaging, pages_manage_metadata,
        instagram_basic, instagram_manage_messages

2. RUNTIME
   ─ Cliente escribe al DM / Messenger → webhook /webhooks/messenger (entry[]/changes[])
   ─ Payload identifica: page_id (o instagram_user_id) + PSID del emisor
   ─ page_id → tenant → ChatService responde
   ─ Respuesta → Graph API /me/messages con PSID (dentro de 24h = gratis)

3. VENTANA 24H
   ─ Fuera de 24h: mensaje de marketing aprobado (requiere revisión)
```

### 4.2 Diferencias vs WhatsApp

- El contacto se identifica por **PSID** (user id de Messenger), no teléfono.
- Unificación con CRM: vía **Matching API** (con opt-in del usuario) o cuando el usuario deja su teléfono en el chat.
- El "número" del tenant es su **Page de Facebook** (no hay migración de número).

### 4.3 Embarque

```
Botón "Conectar" → login con Facebook → elegir página → permisos
→ se obtiene page_access_token + page_id (menos estricto que WhatsApp)
```

- Fase 3+: implementación incremental (primero Instagram, luego Facebook).

---

## 5. Telegram

Es el canal más simple: **sin revisión de Meta ni ventana de 24h ni plantillas**.

### 5.1 Flujo

```
1. REQUISITOS
   └─ Crear bot con @BotFather → token del bot (único secreto)

2. RUNTIME
   ─ Cliente escribe al bot (t.me/el_bot)
   ─ Telegram → webhook /webhooks/telegram/{token-tenant}
   ─ Payload: chat_id + text → tenant → ChatService responde
   ─ Respuesta → API sendMessage / bot{token} con chat_id (siempre libre)

3. EMBARQUE
   └─ El cliente crea el bot (o la plataforma se lo crea) → token cifrado
     en `integrations` + registro del webhook
```

### 5.2 Diferencias

- El token del bot se **cifra por tenant** (único secreto).
- Se puede responder **sin límite de ventana** (ideal para el orquestador).
- El `chat_id` (o `username`) identifica al usuario.

---

## 6. Email

| Aspecto | Decisión por defecto |
|---|---|
| Envío | API transaccional (Resend / Postmark / Mailgun) vía `send_message` |
| Dominio del tenant | SPF/DKIM configurados para el dominio de la empresa |
| Recepción / inbound | Webhook de email → parsear → unir a conversación del contacto |
| Hilos | Continuidad por `thread-id` mapeado al contacto |

- Útil para: seguimiento de cotizaciones, notificaciones, postventa.

---

## 7. N8N (capa de automatización)

### 7.1 Rol

Conectar la plataforma con sistemas externos (ERP, CRM, Calendar, Payments, Slack, Google Sheets, etc.) **sin implementar cada integración en el core**.

### 7.2 Patrón de uso

```
Plataforma ──webhook──> N8N ──> sistema externo
Sistema externo ──webhook──> N8N ──> Plataforma (con contexto de tenant)
```

- **Hacia N8N**: la plataforma dispara `webhook_outbox` (eventos: `lead.created`, `conversation.closed`, `quote.generated`, `agent.action`).
- **Desde N8N**: N8N llama a la API de la plataforma con un token de integración por tenant (nunca credenciales globales de la app).

### 7.3 Gobernanza

- Cada tenant tiene su `integration` N8N (URL + secret) y sus workflows.
- El webhook del plugin valida firma/secret y fija `tenant_id` antes de procesar.
- Rate limits y reintentos con backoff en `webhook_outbox`.

---

## 8. Otras integraciones

| Sistema | Enfoque | Estado |
|---|---|---|
| Google Calendar / agenda | API de Calendar vía Booking Agent | Fase 4 |
| Payments (Stripe) | Cashier para billing de la plataforma; enlaces de pago para el tenant | Fase 5 |
| ERP / CRM externo | Vía N8N + webhooks | Fase 5 |
| Slack | Notificación a operadores vía N8N | Opcional |

---

## 9. Webhooks (diseño)

### 9.1 Entrantes (a la plataforma)

- Endpoint público por canal: `/webhooks/{channel}` con verificación de firma (HMAC/secret cifrado por tenant).
- Antes de tocar datos: resolver tenant y `set_config('app.tenant_id', ...)`.
- Idempotencia por `X-Webhook-Id` / hash de payload.

### 9.2 Salientes (`webhook_outbox`)

- Eventos con retry exponencial (1, 5, 30 min…) y dead-letter para inspección.
- Payload mínimo y `tenant_id` explícito.

---

## 10. Seguridad de integraciones

- Secretos cifrados con clave de la app (Laravel `Crypt` / KMS).
- Tokens de integración por tenant, rotables.
- Auditoría de cada llamada saliente/entrante.
- Revisión de permisos de Meta (scopes mínimos).
- Cumplimiento LGPD en datos de clientes que cruzan canales (consentimiento y finalidad).

---

## 11. Pendientes de decisión

- [ ] **WhatsApp: canal oficial (Meta) vs bridge** → recomendado: oficial (A).
- [ ] **Embarque WhatsApp**: completar Business Verification de la plataforma y Embedded Signup.
- [ ] Proveedor de email transaccional (Resend / Postmark / Mailgun).
- [ ] Si el público objetivo es Brasil, revisar requisitos Meta para números + LGPD y WhatsApp Business API en BRL.
- [ ] Telegram como primer canal activable en producción (más barato y sin revisión) mientras Meta se aprueba.
