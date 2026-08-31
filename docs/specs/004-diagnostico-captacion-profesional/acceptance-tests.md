# Pruebas de aceptación — diagnóstico profesional

| ID | Escenario | Resultado esperado |
|---|---|---|
| DIA-01 | diagnóstico nuevo sin datos | estado `empty`; no permite publicar |
| DIA-02 | profesional guarda datos parciales | estado `draft`; puede retomar sin duplicar |
| DIA-03 | falta titularidad o consentimiento | estado `needs_information`; se explica el bloqueo |
| DIA-04 | varios titulares con consentimiento incompleto | no permite `ready_for_publication` |
| DIA-05 | hipoteca, carga o herencia declarada | marca revisión restringida y recomienda experto |
| DIA-06 | inmueble con VPO o usufructo | no confirma viabilidad; escala a revisión competente |
| DIA-07 | faltan comparables | muestra `Sin datos comparables`; no inventa rango |
| DIA-08 | precio aspiracional sobre evidencia | muestra advertencia y escenarios, sin bloquear guardar |
| DIA-09 | ACM orientativa disponible | separa estimación, fuente, fecha y tasación oficial |
| DIA-10 | operación de alquiler | aplica reglas parametrizadas y no importa automáticamente las de venta |
| DIA-11 | compartir diagnóstico | aplica visibilidad por capas y registra acceso |
| DIA-12 | Vera recibe pregunta jurídica | informa límite y recomienda abogado/notario |
| DIA-13 | usuario solicita publicación | solo convierte a checklist si mínimos completos |
| DIA-14 | dato declarado contradice documento | marca inconsistencia y requiere revisión |
| DIA-15 | usuario sin permiso intenta ver datos restringidos | deniega acceso sin revelar el contenido |

## Criterios de aprobación

- Cero datos inventados.
- Cero publicación sin mínimos definidos.
- Cero exposición de campos `restricted` sin autorización.
- Cada escalado indica motivo y profesional recomendado.
- Los estados son reproducibles y auditables.
