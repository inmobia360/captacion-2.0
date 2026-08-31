# Tareas — Spec 004

- [x] T1. Definir modelo de datos del diagnóstico y clasificación de sensibilidad.
  RF: RF-1..RF-3, RF-8. Hecho cuando: cada campo tiene grupo, origen, sensibilidad, estado y reglas de protección. Evidencia: `docs/contracts/captation-diagnosis-data-v1.md`; la implementación y retención legal quedan pendientes.
- [x] T2. Diseñar checklist de entrevista y estados de completitud.
  RF: RF-1..RF-3, RF-9. Hecho cuando: bloques, mínimos, faltantes, borrador, experto y publicación tienen comportamiento definido. Evidencia: `docs/contracts/captation-checklist-v1.md`.
- [x] T3. Definir escenarios de precio y evidencia de mercado.
  RF: RF-4..RF-6. Hecho cuando: precio anunciado, estimación, tasación y cierre aparecen separados, con escenarios, fuentes, fecha e incertidumbre. Evidencia: `docs/contracts/pricing-scenarios-v1.md`.
- [x] T4. Definir reglas de escalado profesional y límites de Vera.
  RF: RF-7. Hecho cuando: capacidades permitidas, límites, categorías de experto y formato de respuesta segura están documentados. Evidencia: `docs/contracts/vera-escalation-v1.md`.
- [x] T5. Crear pruebas de aceptación con casos límite.
  RF: todos. Hecho cuando: titulares múltiples, cargas, herencia, falta de comparables, sobrevaloración, alquiler, permisos y escalado están cubiertos. Evidencia: `acceptance-tests.md`; ejecución pendiente de entorno controlado.
- [x] T6. Implementar una primera versión de borrador sin bloqueo financiero. Backend aislado en `api/diagnoses.php` con persistencia en `captation_diagnoses`; no publica, no hace matching y no consume créditos. El onboarding guarda la URL de portal como fuente del borrador y redirige a revisión. Validado con `node --check assets/js/app.js`, `php -l api/diagnoses.php` y `php -l api/database.php`.
  RF: RF-1..RF-3, RF-9. Hecho cuando: el profesional puede guardar y retomar un diagnóstico sin exponer datos sensibles.

## Decisión previa a T6

La opción recomendada es una tabla independiente `captation_diagnoses`. El plan
debe aprobarse antes de crear la migración o el endpoint.
