# Brecha entre onboarding actual y contrato v1

## Implementación localizada

- Modal conversacional de Vera en `index.php`/`assets/js/app.js`.
- Persistencia local de finalización mediante `captacion_onboarding_completed_v1`.
- Guía adicional para usuarios con cero actividad dentro del panel privado.
- Acciones existentes hacia búsqueda, publicación, importaciones, panel y referidos.
- Lectura de estado de créditos mediante `api/credits.php?action=status`.

## Diferencias frente a Spec 003

| Requisito | Situación actual | Brecha |
|---|---|---|
| estado de cuenta | parcialmente visible | falta estado unificado backend |
| plan avanzado | se deriva en parte de `plan_type`/rol | falta entitlement explícito |
| `full_tools` e IA | existe lógica de acceso profesional y Vera | falta contrato común de capacidades |
| caducidad | algunos componentes la consultan | `credits/status` no la expone claramente |
| CTA publicar/buscar | ya existe en Vera y panel | falta pantalla canónica post-registro |
| error de créditos | hay fallbacks parciales | debe impedir cualquier saldo inventado |
| reanudación | usa localStorage y sesión | debe coordinarse con estado server-side |
| tres dominios | implementación observada principalmente en principal | `pro` pendiente de contrato/inspección |

## Decisión de implementación

T4 no debe consistir en sustituir el modal actual. Debe:

1. conservar la experiencia Vera existente;
2. añadir un estado canónico de onboarding encima;
3. consumir plan, capacidades y saldo desde contratos backend;
4. mantener las CTAs actuales como acciones derivadas;
5. eliminar gradualmente valores locales que contradigan el backend;
6. probar primero con perfiles sintéticos.

## Estado

Análisis completado. Implementación pendiente de contrato de entitlements y
pruebas automatizadas.
