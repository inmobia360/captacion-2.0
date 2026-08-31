# Tareas — Spec 001

## T001 — Inventario de identidad actual — COMPLETADA EN CÓDIGO LOCAL

- Responsable: `auditor` + `release-devops`
- Fase: descubrimiento
- Prioridad: P0
- Dependencia: acceso de lectura a los despliegues
- Hecho cuando: existe una tabla con emisor de sesión, cookies, expiración,
  roles, base de usuarios y rutas protegidas de cada superficie.
- Evidencia: `current-inventory.md`. Falta contrastar la configuración real de
  los despliegues antes de cerrar la tarea completamente.

## T002 — Contrato de intercambio entre dominios

- Responsable: `security-privacy` + `app-developer`
- Fase: diseño
- Prioridad: P0
- Dependencia: T001
- Hecho cuando: se decide el patrón, audiencia, TTL, nonce, revocación,
  CSRF, CORS e idempotencia, con casos de rechazo documentados.

## T003 — Matriz de autorización

- Responsable: `crm-operations` + `payments-entitlements`
- Fase: diseño
- Prioridad: P0
- Dependencia: T001
- Hecho cuando: cada superficie tiene roles y capacidades permitidas, y el CRM
  queda explícitamente restringido a staff en backend.

## T004 — Pruebas de contrato y regresión

- Responsable: `quality-assurance-e2e` + `app-developer`
- Fase: validación
- Prioridad: P1
- Dependencia: T002 y T003
- Hecho cuando: hay pruebas para acceso anónimo, profesional, premium,
  staff, sesión caducada, replay, cambio de rol y acceso directo al CRM.

## T005 — Implementación reversible en staging

- Responsable: `app-developer` + `release-devops`
- Fase: implementación
- Prioridad: P1
- Dependencia: T004 y autorización explícita
- Hecho cuando: el flujo funciona en staging, se conserva el acceso anterior,
  existe backup y rollback documentado, y la auditoría no encuentra P0/P1.
