# Tareas — Spec 002

- [x] T1. Inventariar rutas, CTAs y estados visibles en los tres dominios.
  RF: RF-1..RF-4. Hecho cuando: existe una tabla de recorrido con URL, actor, estado y siguiente acción. Evidencia: `route-inventory.md`; `pro` queda pendiente de captura directa.
- [x] T2. Documentar el contrato de sesión y transición entre dominios.
  RF: RF-3, RF-15. Hecho cuando: existe un contrato propuesto con audiencia, expiración, revocación, replay protection y rollback. Evidencia: `docs/contracts/identity-session-v1.md`. La validación de producción queda pendiente.
- [x] T3. Definir estados canónicos de oportunidad, reserva y operación.
  RF: RF-5..RF-13. Hecho cuando: cada transición y estado está documentado con reglas de crédito, firma, privacidad y cierre. Evidencia: `docs/contracts/business-state-v1.md`; la comparación con producción queda pendiente.
- [x] T4. Crear matriz de datos visibles y protegidos.
  RF: RF-5, RF-8. Hecho cuando: cada campo tiene clasificación, actor autorizado y momento de revelación. Evidencia: `docs/contracts/data-visibility-v1.md`; CRM definido como Staff-only.
- [x] T5. Crear tests de aceptación del recorrido sin efectos financieros.
  RF: RF-1..RF-6, RF-14. Hecho cuando: existe una matriz reproducible para visitante, base, premium, Staff, publicación, búsqueda y fallback. Evidencia: `acceptance-matrix.md`; la ejecución queda pendiente de entorno controlado.
- [x] T6. Crear tests de idempotencia para reserva, consumo y reintentos.
  RF: RF-7, RF-10, RF-11, RF-15. Hecho cuando: existe contrato, respuestas esperadas y catálogo de pruebas para no duplicar saldo, reservas, pagos ni operaciones. Evidencia: `docs/contracts/idempotency-v1.md`; la ejecución queda pendiente de entorno controlado.
- [x] T7. Definir eventos de analítica de producto respetando privacidad.
  RF: RF-1..RF-15. Hecho cuando: los eventos tienen nombre, superficie, actor, propiedades mínimas, métricas y exclusiones de PII. Evidencia: `docs/contracts/product-analytics-v1.md`; la instrumentación queda pendiente.
- [x] T8. Validación final RF por RF y decisión de primera mejora de UX.
  RF: todos. Hecho cuando: cada RF tiene evidencia, resultado y una tarea de implementación priorizada. Evidencia: `validation.md`; la spec queda preparada, no certificada para producción.
