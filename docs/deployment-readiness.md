# Preparación pendiente para despliegue

## Driver de base de datos PHP

- Estado: pendiente de despliegue.
- Motivo: el PHP local dispone de `PDO`, pero no tiene instalado `pdo_sqlite` ni `pdo_mysql`.
- Decisión provisional: no modificar el entorno local durante esta fase de estructuración.
- Antes de desplegar: confirmar el motor elegido y habilitar el driver correspondiente en PHP.
- Validación posterior: ejecutar `php tests/run_tests.php` y conservar el resultado como evidencia del despliegue.

## Criterio de cierre

La tarea se considerará completada cuando el entorno objetivo pueda inicializar la base de datos, ejecutar la suite dinámica sin `could not find driver` y validar la creación de `captation_diagnoses`.
