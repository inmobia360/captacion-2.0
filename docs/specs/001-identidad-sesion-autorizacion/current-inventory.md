# Inventario actual T001 — identidad, sesión y superficies

Fecha: 2026-08-31
Ámbito: `D:\CAPTACION-2.0` (solo lectura)

## Hechos comprobados

| Área | Estado observado | Evidencia |
|---|---|---|
| Enrutamiento | La entrada principal deriva a `crm/index.php` cuando el host comienza por `crm.`; el resto se sirve desde la entrada pública. | `index.php`, líneas 5–8 |
| Sesión pública | La entrada pública inicia una sesión PHP antes de cargar autenticación. | `index.php`, línea 11 |
| Sesión API | `api/auth.php` configura cookie para `.compracaptacion.com`, `Secure`, `HttpOnly`, `SameSite=Lax`. | `api/auth.php`, líneas 19–31 |
| Sesión CRM | CRM y `api/admin/auth.php` inician sesión PHP sin aplicar explícitamente los mismos parámetros de cookie. | `crm/index.php`, líneas 10–12; `api/admin/auth.php`, líneas 13–16 |
| Autoridad de usuario | La autenticación consulta `users`; la sesión pública usa `$_SESSION['user_id']`. | `api/auth.php`, líneas 39–45 |
| Token alternativo | La API también acepta `Authorization: Bearer` o `X-User-Token` y busca el valor en `users.verification_token`. | `api/auth.php`, líneas 47–60 |
| Separación CRM | CRM exige `role=admin|staff` y `verification_status=approved` tanto en página como en guardas API. | `crm/index.php`, líneas 17–25; `api/admin/auth.php`, líneas 24–42 |
| Pro | El frontend identifica planes con reglas derivadas de rol y campos de usuario; no hay todavía un intercambio temporal cross-domain implementado. | `api/auth.php`, líneas 285–336 |
| CORS | Solo se declara `Access-Control-Allow-Origin` para el origen exacto de Pro; la defensa POST admite una lista de hosts permitidos. | `api/auth.php`, líneas 7–17 y 34–48 |
| CSRF | Existe validación de `Origin` para POST con origen presente, pero no se observa un token CSRF explícito en este puente. | `api/auth.php`, líneas 34–48 |
| Auditoría | Login, registros y acciones sensibles escriben en `audit_logs` en distintos endpoints. | `api/auth.php`, líneas 225–234 y 410–474; `api/admin/auth.php`, líneas 158–174 |

## Riesgos y decisiones pendientes

### P1 — Token de verificación usado como credencial alternativa

`get_auth_user()` acepta directamente el valor de `verification_token` como
credencial persistente. Debe comprobarse si ese campo se usa también para
verificación de email y si puede revocarse/rotarse. No debe convertirse en una
sesión cross-domain ni viajar como token reutilizable.

### P1 — CRM y público no comparten un contrato explícito de sesión

El CRM usa `staff_user_id`/`admin_user_id`, mientras que la superficie pública
usa `user_id`. Esto ayuda a separar permisos, pero todavía no existe un contrato
formal de expiración, revocación, regeneración de ID de sesión ni audiencia.

### P1 — Dominio de cookie incompatible con staging Hostinger

La cookie configurada para `.compracaptacion.com` no cubre
`snow-jellyfish-183518.hostingersite.com`. El staging debe usar configuración
propia y nunca reutilizar credenciales de producción.

### P2 — Reglas de plan derivadas de rol

La respuesta de login deriva `premium`/`professional_plus` a partir de roles y
de valores especiales. Antes de integrar Pro debe existir un entitlement
server-side con plan, pago y capacidades explícitas.

### P2 — Pruebas dinámicas bloqueadas localmente

La suite informa `SKIPPED` porque el PHP local no tiene drivers PDO. La
validación debe repetirse con `pdo_mysql` o `pdo_sqlite` habilitado.

## Conclusión T001

El inventario inicial está completado con evidencia de código. No es seguro
implementar todavía el intercambio entre dominios hasta confirmar en los tres
despliegues: autoridad real de autenticación, configuración de cookies, uso del
token de verificación, roles activos y estado de pago/entitlements.
