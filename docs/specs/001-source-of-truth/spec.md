# Spec 001 — Fuente de verdad del proyecto

## Contexto y objetivo

El proyecto contiene varias copias y variantes de Compra Captación. Esta spec
define cómo identificar la implementación operativa antes de reorganizarla o
fusionarla, evitando pérdida de funcionalidad o divergencias silenciosas.

## Usuarios / actores

- Director del Proyecto
- Agente de desarrollo
- Auditor técnico

## Historias de usuario

- H1: Como Director del Proyecto quiero identificar una única fuente de verdad para que los cambios futuros sean trazables.
- H2: Como agente quiero conocer qué copias son activas, históricas o prototipos para no modificar la variante equivocada.

## Requisitos funcionales (EARS)

- RF-1: CUANDO se revise el proyecto, EL SISTEMA debe listar cada copia con su ruta, propósito, historial Git, remoto y fecha de modificación relevante.
- RF-2: SI dos copias contienen archivos centrales diferentes, EL SISTEMA debe marcarlas como variantes y prohibir una fusión automática.
- RF-3: CUANDO se seleccione una fuente operativa, EL SISTEMA debe registrar la decisión, su evidencia y las dudas pendientes.
- RF-4: EL SISTEMA debe conservar intactas las copias no seleccionadas hasta que exista una decisión explícita de archivado o eliminación.
- RF-5: SI la fuente local no coincide con producción, EL SISTEMA debe mantener la discrepancia como riesgo abierto y no declararla versión desplegable.

## Requisitos no funcionales

- La revisión debe ser reproducible mediante comandos de inventario y hashes.
- No se deben exponer secretos ni datos personales durante el inventario.
- La decisión debe quedar documentada en Markdown y ser legible por agentes y personas.

## Casos límite

- Una copia con historial Git puede ser más antigua que una copia sin historial.
- Dos archivos con el mismo nombre pueden implementar comportamientos distintos.
- La fecha de modificación local no demuestra por sí sola que una copia esté en producción.
- La producción puede contener cambios no presentes en ninguna copia local.

## Fuera de alcance

- Mover, borrar o renombrar carpetas.
- Fusionar ramas o repositorios.
- Hacer commit, push o despliegue.
- Declarar confirmada la correspondencia con producción sin evidencia adicional.

## Criterios de finalización

- Existe un inventario comparativo documentado.
- La fuente candidata y las copias alternativas están identificadas.
- Las diferencias de archivos centrales están registradas.
- Las dudas que requieren confirmación del Director están enumeradas.

## Dudas abiertas

- [NECESITA ACLARACIÓN] ¿`CompraCaptacion/` es exactamente la carpeta que debe publicarse en el repositorio GitHub principal?
- [NECESITA ACLARACIÓN] ¿Qué partes del repositorio `inmobia360/compracaptacion.premium` deben integrarse en el proyecto principal? El borrador inicial está en `docs/premium-integration-matrix.md`.
- [NECESITA ACLARACIÓN] ¿El repositorio histórico `compracaptacion_antigravity` debe conservarse como referencia o migrarse posteriormente?
- [NECESITA VERIFICACIÓN] ¿Qué commit o artefacto local corresponde a `compracaptacion.com`, `pro.compracaptacion.com` y `crm.compracaptacion.com`?
