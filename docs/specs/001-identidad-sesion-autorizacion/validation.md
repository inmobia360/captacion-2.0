# Validación — Spec 001

## Estado

`PENDIENTE DE EVIDENCIA DE ENTORNO`

## Comprobaciones requeridas

- [ ] Login profesional crea una sesión válida y limitada.
- [ ] Usuario profesional sin plan avanzado no entra en `pro`.
- [ ] Usuario premium obtiene solo `full_tools` y asistencia IA concedidas.
- [ ] Profesional, premium o usuario anónimo no entra en CRM.
- [ ] Staff autorizado entra en CRM y staff no autorizado es rechazado.
- [ ] Código temporal caducado, reutilizado o destinado a otra audiencia falla.
- [ ] Logout, revocación y cambio de rol invalidan accesos sensibles.
- [ ] No aparecen secretos, tokens reutilizables, contraseñas ni datos sensibles
  en URLs, logs o respuestas.
- [ ] Las pruebas dinámicas corren con un driver PDO disponible.
- [ ] Las cookies reales de público, Pro y CRM se verifican tras login con
  `Secure`, `HttpOnly`, `SameSite`, nombre, dominio y expiración correctos.
- [ ] Se confirma que el sitemap declarado en `robots.txt` devuelve XML válido.

## Evidencia que debe adjuntarse

- Inventario de endpoints y cookies.
- Resultados de pruebas automatizadas.
- Registro de auditoría sin datos personales innecesarios.
- Captura o log de staging por cada superficie.
- Decisión aprobada del propietario sobre el patrón de intercambio.

## Criterio de bloqueo

No se puede empaquetar ni desplegar esta spec si falta la autoridad de identidad,
si CRM acepta una sesión profesional o si las pruebas dinámicas siguen sin poder
ejecutarse.
