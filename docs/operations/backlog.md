# Backlog inicial de CAPTACION-2.0

## Estado

La arquitectura de agentes y la copia inicial del proyecto están preparadas. No hay todavía una tarea de implementación autorizada para producción.

## Próximo ciclo recomendado

- [x] Copiar la fuente operativa a `D:\CAPTACION-2.0` y configurar el remoto GitHub.
- [x] Desplegar la arquitectura `.agents` y la matriz de activación.
- [x] Validar PHP y extensiones requeridas en Hostinger; queda pendiente activar
  un driver PDO en el entorno local de pruebas.
- [ ] Confirmar la configuración de staging y producción.
- [ ] Revisar contratos de sesión, entitlements y separación public/Pro/CRM.
- [x] Elegir la primera spec de implementación: 001 identidad, sesión y autorización.
- [x] Preparar plan, tareas y validación antes de modificar el núcleo.
- [x] Ejecutar T001 en código local: inventario de identidad, cookies, roles y
  dominios documentado en `docs/specs/001-identidad-sesion-autorizacion/current-inventory.md`.
- [ ] Contrastar T001 con los tres despliegues y cerrar las decisiones abiertas.
- [ ] Incorporar auditoría SEO externa: verificar indexación, autoridad dispersa,
  canonicales, sitemap y redirecciones antes de sustituir el dominio actual.
- [ ] Ejecutar la puerta SEO de staging definida en
  `docs/operations/seo-staging-release-gate.md`.
- [ ] Resolver como bloqueos previos: sitemap XML válido, política `noindex` de
  Pro y verificación de atributos de cookies tras autenticación.
- [x] Resolver HTTP 500 en la raíz del staging y repetir smoke tests.
- [x] Resolver HTTP 500 de `api/auth.php?action=login`: faltaba la tabla
  `audit_logs`; el login ficticio ya devuelve error controlado.
- [ ] Probar login válido, logout y revocación con una cuenta de staging.

## Regla de la secretaria

Cada tarea deberá incluir responsable, fase, prioridad, dependencia, criterio “Hecho cuando” y evidencia. Las tareas bloqueadas se elevan al CEO; no se cierran por inactividad.
