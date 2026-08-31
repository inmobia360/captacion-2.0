# Clarificación QA — Spec 002

## Ambigüedades detectadas

1. El recorrido cruza tres dominios, pero aún no está confirmado dónde se inicia y valida la sesión.
2. “Activar perfil” puede requerir verificación manual, email verificado o solo completar datos mínimos.
3. La reserva de 72 horas está documentada, pero deben confirmarse sus estados y reglas de liberación de crédito.
4. El momento exacto de revelar cada dato sensible debe definirse campo por campo.
5. El flujo de doble firma necesita contrato documental, versión, expiración y prueba de identidad.
6. El fallback de Vera y de los mapas debe distinguir indisponibilidad temporal de ausencia de datos.

## Riesgos

- Un CTA que lleve a otro dominio sin preservar contexto puede romper el onboarding.
- Mostrar una cifra comercial como dato operativo puede generar expectativas falsas.
- Reintentos simultáneos pueden duplicar reservas, consumos o recompensas.
- Una operación abierta sin siguiente acción clara puede aumentar el abandono.

## Veredicto

La spec puede pasar a planificación solo para documentación, contratos y pruebas.
La implementación de autenticación, reservas y desbloqueos requiere cerrar las
decisiones abiertas de la Spec 001 y confirmar los estados reales en producción.
