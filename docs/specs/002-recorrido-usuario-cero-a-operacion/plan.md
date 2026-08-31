# Plan técnico — Spec 002

## Componentes y cobertura

| Componente | Responsabilidad | RF |
|---|---|---|
| Web pública | propuesta de valor, registro y entrada al recorrido | RF-1, RF-2 |
| Área profesional | onboarding, publicación, búsqueda, reservas y seguimiento | RF-3..RF-12, RF-15 |
| CRM | soporte, revisión y auditoría de estados | RF-9, RF-12, RF-13 |
| API de identidad | sesión, verificación, roles y revocación | RF-3, RF-15 |
| API de marketplace | captaciones, demandas, matching y datos ciegos | RF-4..RF-8 |
| API de créditos | reserva, consumo, liberación y ledger | RF-7, RF-10, RF-11 |
| API de operaciones | aceptación, firma, estados y cierre | RF-9, RF-12, RF-13 |
| Vera/mapas | asistencia y fallback no bloqueante | RF-6, RF-14 |
| Tests | contrato, integración, regresión y recorrido | todos |

## Estrategia

1. Capturar el estado actual de cada paso sin cambiarlo.
2. Definir contratos comunes de identidad, oportunidad, reserva, crédito y operación.
3. Añadir pruebas de aceptación para el recorrido principal y sus fallos.
4. Instrumentar eventos de producto sin registrar PII innecesaria.
5. Mejorar un punto de fricción cada vez, empezando por el primer valor tras el registro.

## Decisiones técnicas pendientes

- Autoridad de sesión entre dominios.
- Estados canónicos de reserva y desbloqueo.
- Contrato de datos ciegos y política campo por campo.
- Fuente de verdad del porcentaje contractual.
- Formato de eventos de recorrido y retención de analítica.

## No hacer en esta fase

- Extraer carpetas físicamente.
- Reescribir `index.php` o `assets/js/app.js` completos.
- Cambiar el modelo de créditos.
- Desplegar o modificar producción.
