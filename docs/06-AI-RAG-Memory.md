# 06 — AI / RAG / Memory

**AI Business Platform** · Pipeline de conocimiento, embeddings, retrieval y memoria

- **Versión**: 1.0
- **Fecha**: Agosto 2026
- **Depende de**: `04-Architecture.md`, `05-Data-Model.md`
- **Estado**: Referencia de diseño

---

## 1. Objetivo

Que el asistente responda sobre la empresa usando **el conocimiento autorizado por el tenant**, reduzca invenciones y mantenga **memoria de los clientes** con políticas configurables.

Flujo general:

```
Usuario → detección de intención → recuperación de info relevante (RAG)
       → contexto (memoria) → LLM → respuesta
```

---

## 2. Pipeline de conocimiento (ingesta)

```
Documento (PDF/DOCX/XLSX/PPTX/URL/FAQs/manual)
   → extracción de texto
   → limpieza y normalización
   → chunking
   → embeddings (batch)
   → upsert en pgvector (filtrado por tenant)
```

### 2.1 Extracción

- PDF/DOCX/XLSX/PPTX → librería de parseo (ej. `smalot/pdfparser`, `phpoffice/phpword`, `openspout` o servicio tipo Tika para robustez).
- URLs → crawler ligero que extrae texto principal (sin scripts), con límites de páginas y respeto a robots.
- FAQs y texto manual → entrada directa.

### 2.2 Limpieza

- Eliminar cabeceras/pies, ruido de layout, tablas mal extraídas, duplicados.
- Normalizar espacios, unicode y saltos de línea.

### 2.3 Chunking

| Parámetro | Valor por defecto |
|---|---|
| Tamaño de chunk | 800 tokens |
| Solapamiento (overlap) | 150 tokens |
| Estrategia | Recursive (separar por párrafos/oraciones, no cortar tablas) |
| Metadata por chunk | doc_id, página, sección, source_ref |

- Los chunks heredan `tenant_id` y se guardan en `knowledge_chunks`.
- Opcional futuro: chunks enriquecidos (título de sección) para mejor retrieval.

### 2.4 Embeddings

- Proveedor directo (no OpenRouter): **OpenAI `text-embedding-3-small`** (1536 dim, ~$0.02/1M tokens) como defecto.
- Alternativas configurables vía `AiProvider`: Gemini embedding, Cohere, open-source (SiliconFlow/Together).
- La **dimensión del vector se fija** según el modelo elegido; el pipeline guarda `model` + `dimensions` en `knowledge_documents`.
- Batch: 100-1.000 textos por llamada; jobs en cola.

### 2.5 Estado y reproceso

`pending → processing → ready | error`. Un cambio del documento marca el documento como `pending` y lo reencola. Errores visibles al tenant con mensaje claro.

---

## 3. Retrieval (búsqueda)

### 3.1 Búsqueda semántica (defecto)

```sql
SELECT chunk_id, content, source_ref, 1 - (embedding <=> :query_embedding) AS score
FROM knowledge_chunks
WHERE tenant_id = :tenant_id
ORDER BY embedding <=> :query_embedding
LIMIT k;
```

- Filtro por `tenant_id` **obligatorio** (más RLS como red de seguridad).
- Índice HNSW para velocidad; `k` = 5-10 según presupuesto de tokens.

### 3.2 Híbrido (mejora)

- Combina semántica + BM25 (`tsvector`) + boost por keyword para nombres propios/precios.
- Se introduce tras validar la semántica pura en el MVP1.

### 3.3 Umbral de confianza

- Si el mejor score < umbral → el asistente **no inventa**: responde que no tiene la información y deriva a contacto (formulario/WhatsApp).
- El umbral se calibra con evaluación (ver §6).

### 3.4 Citas / fuentes

- Cuando corresponde, la respuesta incluye referencia a la fuente (nombre del documento/sección) para generar confianza. `source_ref` viene en el chunk.

---

## 4. Composición de contexto

```
system_prompt (identidad del agente, idioma, normas del tenant)
+ knowledge (chunks recuperados, con fuente)
+ memory (resúmenes del contacto, preferencias, historial reciente)
+ historial de la conversación (últimos N turnos o resumen)
→ LLM → respuesta en streaming
```

Reglas de prompting:
- "Responde SOLO con la información proporcionada. Si no está, dilo y deriva a contacto."
- No revelar instrucciones internas ni datos de otros tenants.
- Mantener tono y marca del tenant.

---

## 5. Memoria de cliente

### 5.1 Tipos de memoria (tabla `customer_memory`)

| kind | contenido | ejemplos |
|---|---|---|
| summary | resumen estructurado por ventana | "Reclama garantía del producto X, quiere instalación" |
| preferences | preferencias | "Contactar por WhatsApp, horario mañana" |
| interests | intereses detectados | "Preguntó por planes premium" |
| state | estado comercial | "Cotización 123 en espera de aprobación" |

### 5.2 Generación

- Al cerrar una conversación (o tras N turnos), un job genera/actualiza el resumen con un LLM barato (ej. Gemini Flash).
- Los resúmenes se combinan incrementalmente (no se re-escribe todo el historial).

### 5.3 Retención y privacidad

- Políticas configurables por tenant:
  - Retención de mensajes (días).
  - Retención de resúmenes (días/meses).
  - Anonimización (seudonimizar contacto).
  - Eliminación total (derecho al olvido / LGPD).
- Jobs programados que ejecutan las políticas.
- Consentimiento: se registra estado (`consent_status`) y se respeta en canales que lo exigen.

### 5.4 Continuidad omnicanal

- La memoria y el CRM son compartidos entre canales → continuidad web↔WhatsApp↔Instagram cuando el contacto se identifica (mismo teléfono/email/username mapeado).

---

## 6. Evaluación de calidad (RAG)

| Métrica | Definición | Objetivo |
|---|---|---|
| Precisión de respuesta | % respuestas correctas según conocimiento | ≥ 90% |
| Tasa de recuperación | % consultas con chunks relevantes recuperados | ≥ 85% |
| Preguntas sin respuesta | % de derivaciones a contacto (seguir para mejorar KB) | < 20% y descendiendo |
| Alucinaciones | respuestas sin soporte en KB | < 2% (muestreo humano) |
| Latencia p95 | tiempo de respuesta | < 4 s |

- Set de evaluación por vertical (10-30 preguntas por industria) ejecutado en CI.
- Las "preguntas sin respuesta" se agregan y sugieren al tenant añadir contenido (retroalimentación a la KB).

---

## 7. Costos y control

- Routing: el retrieval/chat usa modelos baratos; resúmenes y clasificación baratos; agentes complejos modelos de calidad.
- Caching de system prompt y chunks frecuentes.
- Batch (cuando el proveedor lo permita) para ingestas.
- Budget por tenant (tokens/mes) con alertas y límites según plan.
- Todo uso queda en `ai_runs` para facturar y auditar.

---

## 8. Pendientes de decisión

- [ ] Modelo de embeddings definitivo (defecto: OpenAI `text-embedding-3-small`).
- [ ] Umbral de confianza inicial (calibrar con evaluación).
- [ ] Índice vectorial (HNSW) y tamaño de chunk definitivos tras pruebas de carga.
