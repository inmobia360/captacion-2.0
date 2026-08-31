# Matriz de aceptación T5 — recorrido unificado

## Perfiles de prueba

| Perfil | Cuenta | Plan | Acceso esperado |
|---|---|---|---|
| Visitante | no | ninguno | web pública únicamente |
| Profesional base | activa | gratuito/base | funciones base autorizadas; sin `pro` |
| Premium activo | activa | `advanced` | `pro`, `full_tools` y `ai_assistance` |
| Premium impagado | activa | `advanced` | sin sesión premium hasta resolver pago |
| Staff | activa | interno | CRM según categoría Staff; no depende del plan premium |
| Usuario suspendido | suspendida | cualquiera | ningún recurso privado |

## Casos de aceptación

| ID | Escenario | Resultado esperado | RF |
|---|---|---|---|
| AT-01 | Visitante abre `/inicio` | ve propuesta, datos ciegos y CTA pública | RF-1 |
| AT-02 | Visitante inicia registro | conoce créditos, duración y límites antes de enviar | RF-2 |
| AT-03 | Usuario base entra en `pro` | acceso denegado con siguiente paso comprensible | RF-3 |
| AT-04 | Premium con pago activo entra en `pro` | sesión premium con `full_tools` e IA concedidas | RF-3, RF-4 |
| AT-05 | Premium con pago inactivo entra en `pro` | no recibe capacidades premium | RF-3, RF-4 |
| AT-06 | Usuario profesional entra en CRM | acceso denegado aunque tenga sesión válida | RF-3 |
| AT-07 | Staff autorizado entra en CRM | acceso al módulo permitido y auditoría de login | RF-3, RF-5 |
| AT-08 | Profesional publica captación | previsualiza, oculta datos sensibles y confirma estado | RF-5 |
| AT-09 | Colaborador busca oportunidad | ve coincidencia explicable y datos ciegos | RF-6, RF-8 |
| AT-10 | Usuario reserva | ve coste y duración antes de confirmar; crédito queda reservado | RF-7 |
| AT-11 | Reserva repetida | no duplica reserva ni movimiento de ledger | RF-10, RF-15 |
| AT-12 | Ambas partes firman | se habilita solo el acceso autorizado | RF-9, RF-10 |
| AT-13 | Reserva expira | se libera/revierte según regla y se informa al usuario | RF-11 |
| AT-14 | Vera no responde | aparece fallback sin bloquear la acción principal | RF-14 |
| AT-15 | Operación se cierra | usa valores contractuales en el resultado | RF-12, RF-13 |

## Regla de ejecución

Estos casos son criterios de aceptación. No autorizan por sí solos cambios en
producción ni pruebas destructivas sobre datos reales. Primero deben ejecutarse en
un entorno controlado con datos sintéticos y después como regresión no destructiva.
