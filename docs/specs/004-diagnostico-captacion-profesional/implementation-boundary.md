# Frontera de implementación T6

## Hallazgo

La tabla `records` tiene estados y datos de captación, pero el endpoint actual de
creación (`api/records.php?action=create`) valida mínimos y persiste directamente
como `active`. No existe aún una entidad o endpoint específico de diagnóstico.

## Decisión segura

No reutilizar `records` cambiando silenciosamente el estado ni publicar datos
parciales. La primera implementación debe ser un borrador aislado que:

- requiera autenticación;
- pertenezca a un único usuario;
- guarde solo campos del diagnóstico;
- permita retomar y actualizar el mismo borrador;
- no consuma créditos;
- no ejecute matching;
- no sea visible en marketplace;
- no revele campos restringidos;
- conserve versión, actor y timestamps.

## Opciones a evaluar en el plan técnico

1. Tabla `captation_diagnoses` independiente, recomendada para separar ciclo de vida.
2. Extensión de `records` con relación explícita y migración reversible.

La opción elegida debe incluir migración, rollback, autorización, retención,
tests y compatibilidad SQLite/MySQL antes de tocar producción.

## Resultado

T6 queda preparada conceptualmente, pero no implementada. Se necesita aprobar el
modelo de persistencia antes de crear endpoint o migración.
