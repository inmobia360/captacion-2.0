# Auditoría SEO externa — contexto para CAPTACION-2.0

Fecha de incorporación: 2026-08-31  
Fuente: informe aportado por el propietario. Estado: evidencia externa pendiente
de verificación con Search Console, crawling y mediciones propias.

## Hallazgo estratégico

La autoridad e indexación parecen dispersas entre `compracaptacion.com`,
`captacion.app` e `inmobia360.com`. El dominio SEO principal propuesto para el
producto es `compracaptacion.com`. Esto requiere inventario de URLs, canonicales,
redirecciones 301 y control de duplicidad antes de sustituir el sitio actual.

## Prioridades incorporadas

### P0 — Migración e indexabilidad

- Confirmar Search Console, sitemap, `robots.txt`, respuestas HTTP y canonicales.
- Mantener el staging bloqueado para buscadores.
- Preparar mapa de redirecciones desde propiedades antiguas.
- Entregar HTML con H1, title, descripción y enlaces sin depender de JavaScript.

### P1 — Arquitectura temática

Hubs iniciales: `/colaboracion-inmobiliaria/`, `/captacion-inmobiliaria/`,
`/demanda-inmobiliaria/`, `/agentes-inmobiliarios/`, `/mls/`,
`/inteligencia-inmobiliaria/`, `/recursos/` y `/blog/`.

La categoría central será “colaboración inmobiliaria”, conectada con
captaciones, demandas, matching y colaboración protegida.

### P1 — Confianza y contenido

- Separar contenido editorial, análisis jurídico y estimaciones de IA.
- Mostrar autoría, revisión y actualización cuando corresponda.
- Crear conocimiento profesional basado en problemas reales.
- Usar datos estructurados solo cuando el contenido visible los justifique.

### P2 — Escala controlada

- Priorizar páginas agregadas por zona y tipología.
- No indexar automáticamente cada captación individual cambiante o pobre.
- Crear SEO local solo donde exista actividad y contenido real.

## Reglas de producto

- CRM es staff-only, con `noindex, nofollow`, y nunca entra en sitemap.
- Pro es autenticado y no es fuente de contenido privado indexable.
- Las fichas públicas compartibles son ciegas, revocables y mínimas.
- SEO no puede revelar direcciones, contactos, propietarios ni información
  protegida.

## Hipótesis por verificar

- Nivel real de indexación de `compracaptacion.com`.
- URLs de `captacion.app` e `inmobia360.com` que deben redirigirse o mantenerse.
- Consultas, competidores y demanda por intención.
- Core Web Vitals móvil de la versión actual.
