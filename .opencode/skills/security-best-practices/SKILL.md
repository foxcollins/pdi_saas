---
name: security-best-practices
description: Reglas de seguridad y buenas prácticas para PDI_SAAS. Use cuando se vaya a escribir o revisar código que toque datos de tenant, autenticación, autorización, validación de entrada, jobs/queues, webhooks, comandos, subidas de archivos, claves secretas, IA/RAG o consultas a base de datos. También use al añadir dependencias, configurar .env o revisar código por posibles fugas cross-tenant o exposición de secretos.
---

# Security & Best Practices — PDI_SAAS

Reglas obligatorias de seguridad y calidad para todo el código del proyecto.

## 1. Regla de oro multi-tenant

- Toda consulta de datos de tenant pasa por RLS (`set_config('app.tenant_id')`) y **nunca** se omite el contexto de tenant, incluidos jobs, webhooks, comandos y scripts.
- En Eloquent, los modelos de tenant usan el trait `TenantScoped` (global scope). Si un modelo NO lo usa (ej. `Domain`, que es enrutamiento global con RLS deshabilitado), **es obligatorio** filtrar por `tenant_id` explícitamente en controllers/acciones del panel.
- Tests cross-tenant obligatorios: un usuario de tenant A nunca lee/escribe datos del tenant B.

## 2. Secretos y configuración

- Nunca commitear `.env`, API keys, tokens, passwords o credenciales. Revisar `git status` antes de commitear.
- Solo variables `*_KEY`/`*_TOKEN` de entorno; nunca valores hardcodeados en código ni en `config/*.php` (usar `env()`).
- `APP_KEY` de CI/tests puede ser un valor conocido, pero el `.env` local real jamás se sube.
- No registrar secretos en logs (`logger()->info`, `report`) ni en respuestas de error.

## 3. Validación y entrada

- Toda entrada HTTP se valida con `Request::validate()` (reglas por campo, tipos, `max:`). Nunca confiar en el cliente.
- Subidas de archivo: validar `mime` y `max` reales del archivo; no confiar en el nombre ni en el header.
- `storage_key` y rutas: nunca construir rutas con entrada del usuario sin sanitizar; evitar path traversal (no permitir `../`).
- Parámetros de URLs externas (fetch de URLs): restringir protocolos (http/https), validar formato y timeout.

## 4. Autenticación y autorización

- Verificar que la acción pertenece al tenant/usuario correcto (policies o chequeo `tenant_id` en query) en cada endpoint de panel.
- No exponer IDs de otros tenants en listados ni aceptar IDs ajenos en rutas sin verificación.
- El dominio público nunca expone el identificador interno del tenant.

## 5. Jobs, queues y comandos

- Todo job que toque datos de tenant fija `TenantContext::set($model->tenant_id)` al inicio de `handle()`.
- Comandos/scheduled tasks que cruzan tenants resuelven el/los tenant(s) explícitamente y documentan por qué no aplica RLS.
- No encolar modelos sin contexto cuando el worker los deserialice (re-fetch bajo el contexto correcto).

## 6. IA y RAG

- El retrieval de RAG SIEMPRE filtra por `tenant_id`; nunca enviar la base de conocimiento completa al modelo.
- Respuestas knowledge-authorized: si no hay contexto relevante, derivar a contacto, no inventar.
- Límites de costo/tiempo y trazabilidad por tenant (`ai_runs`) desde el día 1.
- El provider de IA es agnóstico (interfaz `AiProvider`); nunca acoplar lógica de negocio a un proveedor concreto.

## 7. Buenas prácticas de código

- Seguir los patrones y librerías ya presentes; no asumir que una librería existe (verificar en `composer.json`/`package.json`).
- Sin comentarios en código salvo que se pida explícitamente.
- Correr Pint (`vendor/bin/pint`) y la suite de tests (`php artisan test`) antes de commitear.
- Commits pequeños y descriptivos; nunca commitear salvo que el usuario lo pida.

## 8. Checklist pre-commit

- [ ] ¿Hay secretos en el diff? (`git diff`)
- [ ] ¿Toda consulta de tenant está aislada (RLS o filtro explícito)?
- [ ] ¿Las entradas están validadas (incluido upload de archivos)?
- [ ] ¿Los jobs fijan contexto de tenant?
- [ ] ¿Pint pasa y los tests están en verde?
- [ ] ¿Documentación `docs/` actualizada si toca una fase?
