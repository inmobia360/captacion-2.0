# Tareas — Spec 003

- [x] T1. Mapear respuestas actuales de identidad, plan y créditos.
  RF: RF-1..RF-3, RF-5, RF-7. Hecho cuando: existe una tabla de fuentes, campos y gaps. Evidencia: `backend-field-map.md`; falta validar `pro` y producción.
- [x] T2. Diseñar el estado de onboarding y sus CTAs.
  RF: RF-1, RF-4, RF-6, RF-9. Hecho cuando: cada perfil tiene estado, mensaje, acción principal, alternativa y reglas de navegación. Evidencia: `docs/contracts/onboarding-state-v1.md`.
- [x] T3. Añadir pruebas de aceptación por perfil.
  RF: todos. Hecho cuando: base, premium activo, premium inactivo, pendiente, suspendido, Staff y fallos de dependencias están cubiertos. Evidencia: `acceptance-tests.md`; ejecución pendiente de entorno controlado.
- [ ] T4. Adaptar el renderizado del onboarding existente al estado canónico.
  RF: RF-1..RF-6, RF-9. Hecho cuando: una única pantalla usa datos del backend y navega sin duplicados, conservando Vera y cubriendo la brecha documentada en `current-implementation-gap.md`.
- [ ] T5. Implementar estados de error y reanudación.
  RF: RF-7, RF-8. Hecho cuando: timeout, saldo no disponible y abandono se recuperan sin datos inventados.
- [ ] T6. Validar analítica y accesibilidad.
  RF: todos. Hecho cuando: eventos definidos, PII excluida y navegación por teclado verificada.
