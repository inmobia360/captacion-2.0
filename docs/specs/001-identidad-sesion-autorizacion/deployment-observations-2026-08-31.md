# Contraste de despliegues reales — 2026-08-31

Método: consultas HTTP públicas de solo lectura a los tres dominios y a
`robots.txt`/`sitemap.xml`. No se realizaron intentos de login ni cambios
remotos.

## Resultados

| Superficie | Resultado | Observaciones |
|---|---|---|
| Público `/inicio` | HTTP 200 | HTML grande, `index, follow`, canonical a `https://compracaptacion.com/`, H1 y propuesta de colaboración visibles. Responde `PHPSESSID` con `path=/; secure`; no se observaron `HttpOnly` ni `SameSite` en esa respuesta. |
| Pro `/` | HTTP 200 | Panel de acceso, sin `robots` explícito observado y sin canonical observado. No se emitió cookie en la respuesta anónima. |
| CRM `/` | HTTP 200 | `noindex, nofollow`, H1 “Acceso al Panel Staff”. Responde `PHPSESSID` con `path=/; secure`; no se observaron `HttpOnly` ni `SameSite` en esa respuesta. |
| Público `robots.txt` | HTTP 200 | Bloquea `/api/`, `/admin/`, `/area-privada/` y `/crm/`; declara `https://compracaptacion.com/sitemap_index.xml`. |
| Público `sitemap.xml` | HTTP 200 | Devuelve HTML de la aplicación, no un sitemap XML. Es una señal de ruta inexistente o fallback incorrecto. |

## Confirmaciones

- El dominio público ya contiene señales SEO útiles: title, description,
  canonical, H1 y `index, follow`.
- CRM está explícitamente fuera de indexación en HTML, coherente con su uso
  exclusivo para staff.
- Los dominios están físicamente separados en producción y no se ha observado
  todavía un intercambio cross-domain en las respuestas anónimas.

## Hallazgos prioritarios

### P1 — Sitemap declarado no válido en el despliegue actual

`robots.txt` declara `sitemap_index.xml`, pero la ruta comprobada
`/sitemap.xml` devolvió HTML de la aplicación. Hay que consultar y validar la
URL declarada exacta, generar XML válido y comprobar que solo incluye rutas
indexables públicas.

La nueva versión incluye `sitemap.xml` estático con URLs públicas seleccionadas;
no debe enviarse a producción hasta comprobarlo en staging y revisar el mapa de
URLs definitivo.

### P1 — Cookies de despliegue necesitan verificación

Las respuestas públicas y CRM exponen `PHPSESSID` con `Secure`, pero no se
observan `HttpOnly` ni `SameSite`. Puede haber diferencias de servidor o de
respuesta, por lo que debe verificarse en login y en todas las rutas privadas.
No se debe implementar SSO hasta fijar estos atributos y separar nombres,
dominios y audiencias de sesión.

### P1 — Pro necesita política SEO explícita

La pantalla Pro no muestra un `noindex` observable en la respuesta anónima. Al
ser una superficie premium, debe quedar fuera del sitemap y declarar una
política explícita de no indexación, especialmente para rutas autenticadas.

### P2 — Canonical de ruta pública

`/inicio` canonicaliza a `/`. Es válido si `/` es la única URL principal, pero
debe decidirse antes de la migración para evitar duplicidad entre `/` e
`/inicio`.

## Limitaciones

- No se verificaron Search Console, cookies tras autenticación ni configuración
  interna de Hostinger.
- No se probaron credenciales ni se accedió a datos privados.
- Las conclusiones sobre indexación histórica requieren Search Console y logs.
