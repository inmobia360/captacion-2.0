# Matriz de visibilidad y protección de datos v1

## Regla general

La interfaz nunca decide por sí sola qué puede ver una persona. El backend
autoriza cada campo según actor, recurso, estado y finalidad.

## Actores

| Actor | Alcance |
|---|---|
| Visitante | contenido público y oportunidades con datos ciegos |
| Profesional base | sus propios registros y datos colaborativos autorizados |
| Usuario premium avanzado | capacidades `full_tools`, asistencia IA y datos premium autorizados por su plan |
| Colaborador | datos mínimos de una oportunidad y datos desbloqueados tras el flujo válido |
| Staff | operación interna según categoría y RBAC |
| Master admin | administración máxima, siempre auditada |

`crm.compracaptacion.com` es exclusivamente Staff. Un usuario público,
profesional base o premium nunca debe entrar en el CRM por tener una cuenta válida
en otro dominio.

## Datos por etapa

| Dato | Visitante | Profesional antes de colaborar | Tras autorización válida | CRM Staff |
|---|---:|---:|---:|---:|
| título, tipo, zona general | sí | sí | sí | sí |
| precio y características públicas | sí | sí | sí | sí |
| coincidencia y nivel de confianza | limitado | sí | sí | sí |
| dirección exacta | no | no | solo alcance autorizado | según permiso y finalidad |
| teléfono/email del propietario | no | no | solo alcance autorizado | según permiso y finalidad |
| datos registrales/catastro | no | no | solo alcance autorizado | según permiso y finalidad |
| saldo y ledger del profesional | no | solo el propio | solo el propio | agregado o detalle según rol |
| PII de otros usuarios | no | no | mínimo necesario | mínimo necesario y auditado |
| credenciales, hashes y tokens | nunca | nunca | nunca | nunca |

## Reglas de presentación

- Lo estimado debe mostrar fuente, fecha y nivel de confianza.
- Lo contractual debe estar separado visualmente de lo estimado.
- Los datos protegidos deben mostrar el motivo y el siguiente paso, no un vacío confuso.
- Los errores no deben confirmar la existencia de información privada.
- Toda lectura sensible debe poder auditarse con actor, recurso, finalidad y resultado.

## Eventos auditables

`view_public`, `view_private`, `reserve_access`, `accept_collaboration`,
`sign_contract`, `unlock_data`, `staff_view`, `staff_export`, `access_denied`.

Los eventos no deben incluir contraseñas, tokens ni PII innecesaria.
