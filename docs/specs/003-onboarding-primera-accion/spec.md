# Spec 003 — Onboarding y primera acción útil

## Contexto y objetivo

Después del registro, el usuario debe entender rápidamente qué puede hacer,
cuánto valor tiene disponible y cuál es el siguiente paso. El onboarding debe
reducir abandono sin ocultar límites, costes, verificación o protección de datos.

## Actores

- Profesional recién registrado.
- Profesional base ya verificado.
- Usuario premium avanzado.
- Servicio de identidad y créditos.

## Historias de usuario

- H1: Como usuario recién registrado quiero saber si mi cuenta está lista para usar.
- H2: Como profesional quiero elegir entre publicar una captación y buscar una oportunidad.
- H3: Como usuario quiero conocer mis créditos, caducidad y límites antes de usarlos.
- H4: Como usuario premium quiero ver qué herramientas y asistencia IA incluye mi plan.

## Requisitos funcionales (EARS)

- RF-1: CUANDO un usuario complete el registro y acceda por primera vez, EL SISTEMA debe mostrar un resumen de cuenta y un siguiente paso claro.
- RF-2: CUANDO se muestre el resumen, EL SISTEMA debe indicar estado de verificación, plan, créditos disponibles y fecha de caducidad cuando aplique.
- RF-3: CUANDO el usuario no esté verificado, EL SISTEMA debe explicar qué falta y qué acciones siguen disponibles sin prometer acceso no autorizado.
- RF-4: CUANDO el usuario esté en el área base, EL SISTEMA debe ofrecer como acciones principales publicar una captación y buscar una oportunidad.
- RF-5: CUANDO el usuario tenga plan avanzado activo, EL SISTEMA debe mostrar `full_tools` y `ai_assistance` solo si el backend los concede.
- RF-6: CUANDO el usuario seleccione una acción, EL SISTEMA debe conservar el contexto y llevarlo a la ruta correcta sin duplicar sesión ni registro.
- RF-7: SI el servicio de créditos no responde, EL SISTEMA debe mostrar estado no disponible y no inventar saldo.
- RF-8: SI el usuario abandona el onboarding, EL SISTEMA debe poder retomarlo sin repetir efectos ni perder la cuenta.
- RF-9: EL SISTEMA debe mostrar una explicación breve de datos ciegos y de cuándo puede existir desbloqueo autorizado.

## Requisitos no funcionales

- El onboarding debe ser responsive y accesible.
- No debe bloquear la navegación pública ni el CRM Staff.
- Los estados deben proceder del backend, no de valores hardcodeados en frontend.
- No debe registrar PII innecesaria en analítica.

## Casos límite

- Registro completado pero email pendiente.
- Verificación pendiente o rechazada.
- Plan premium cancelado durante la sesión.
- Créditos caducados o saldo cero.
- Usuario que ya completó el onboarding.
- Error de red al resolver entitlements.
- Usuario Staff que no debe ver onboarding profesional.

## Fuera de alcance

- Rediseñar todo el panel privado.
- Cambiar el bono de bienvenida o las reglas del ledger.
- Crear automáticamente una captación o una demanda.
- Implementar todavía la sesión compartida entre dominios.

## Criterios de finalización

- Cada perfil ve solo sus capacidades autorizadas.
- El usuario puede elegir una primera acción desde una única pantalla.
- No se muestra saldo ficticio ante errores.
- Las rutas y CTAs tienen pruebas de aceptación.
- El onboarding puede repetirse o retomarse sin efectos duplicados.
