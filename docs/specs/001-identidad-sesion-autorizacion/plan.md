# Plan de implementación — Spec 001

## Objetivo del ciclo

Definir y validar la autoridad de identidad, el intercambio seguro entre
superficies y la separación estricta entre profesionales, usuarios premium y
staff del CRM, sin cambiar todavía el acceso de producción.

## Orden

1. Inventariar el comportamiento actual de login, sesión, cookies, roles y
   dominios.
2. Elegir el patrón de intercambio entre dominios con base en la evidencia.
3. Formalizar los contratos de diagnóstico y los estados de autorización.
4. Añadir pruebas negativas antes de implementar cambios de sesión.
5. Implementar primero en staging con rollback independiente por superficie.
6. Auditar seguridad, privacidad y recorridos E2E.

## Dependencias

- Acceso de lectura a los tres despliegues actuales.
- Confirmación de la autoridad de autenticación y de la base de usuarios.
- PHP 8.x con `pdo_mysql` o `pdo_sqlite` en el entorno de prueba.
- Decisión del propietario sobre cookie compartida frente a intercambio de un
  solo uso.

## Fuera de este ciclo

- Migrar cuentas o contraseñas.
- Cambiar producción.
- Conceder `full_tools` o asistencia IA.
- Hacer accesible el CRM a usuarios públicos.
