# Plan técnico — Spec 004

## Alternativas

| Opción | Ventaja | Riesgo | Decisión |
|---|---|---|---|
| Extender `records` | menos tablas y reutiliza columnas | mezcla borrador con publicación, matching y métricas | descartada para MVP |
| `captation_diagnoses` independiente | ciclo aislado, permisos claros y migración reversible | requiere endpoint, tabla y relación posterior | recomendada |

## Diseño recomendado

Crear `captation_diagnoses` con:

- `id` e `user_id`;
- `status` (`draft`, `in_review`, `needs_information`, `needs_expert`, `ready_for_publication`, `archived`);
- `record_type` y referencia opcional a `records` solo después de publicar;
- campos estructurados de entrevista;
- `source_metadata` y clasificación de datos;
- `completeness_score` informativo;
- `version`, `created_at`, `updated_at`;
- `deleted_at` y campos de auditoría.

## Endpoint inicial

`api/diagnoses.php`

- `GET?action=list`: solo diagnósticos del usuario autenticado;
- `GET?action=get&id=`: propietario o permiso Staff explícito;
- `POST?action=create`: crea borrador, sin publicar;
- `POST?action=update`: actualiza el borrador del propietario;
- `POST?action=archive`: archivado reversible y auditado.

No habrá endpoint de publicación automática en esta primera iteración.

## Controles

- autenticación y autorización server-side;
- PDO preparado;
- validación de enums, longitudes y tipos;
- captura de `Throwable` y JSON consistente;
- protección CSRF si usa sesión por cookie;
- logs sin PII innecesaria;
- transacción para crear/actualizar y versión optimista para conflictos;
- pruebas SQLite y MySQL compatibles.

## Tareas de implementación

1. Migración reversible de tabla.
2. Endpoint autenticado de borradores.
3. Tests de propiedad, estados y errores.
4. Adaptador frontend de guardado y reanudación.
5. Validación de que no aparece en marketplace ni matching.

## No incluido

- cálculo ACM productivo;
- scoring automático definitivo;
- carga documental sensible;
- publicación;
- integración premium o CRM.
