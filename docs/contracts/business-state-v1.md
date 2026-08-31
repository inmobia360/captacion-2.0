# Contrato de estados de negocio v1 — borrador

## Objetivo

Definir estados canónicos compartidos por la web pública, el área profesional,
el CRM y los módulos premium.

## Oportunidad

| Estado | Significado | Visible públicamente | Acción principal |
|---|---|---:|---|
| `draft` | creada pero no publicada | no | completar y previsualizar |
| `published` | disponible en marketplace | sí, con datos ciegos | explorar o solicitar colaboración |
| `paused` | temporalmente fuera de circulación | no | reactivar si procede |
| `matched` | existe coincidencia relevante | limitada | revisar compatibilidad |
| `archived` | retirada sin operación activa | no | consultar historial autorizado |

La etiqueta `matched` no debe implicar que existe una operación ni que el match
ha sido aceptado por las partes.

## Reserva de acceso

| Estado | Significado | Crédito |
|---|---|---|
| `requested` | solicitud iniciada, aún no confirmada | no consumir definitivamente |
| `reserved` | reserva activa durante la ventana aprobada, documentada actualmente como 72 horas | bloqueado/reservado |
| `accepted` | colaboración aceptada por la parte requerida | pasa a consumo definitivo solo según regla aprobada |
| `expired` | terminó la ventana sin continuidad | liberar o revertir según ledger |
| `rejected` | solicitud rechazada | liberar o revertir según ledger |
| `cancelled` | cancelación válida antes del cierre | liberar o revertir según regla |

Cada transición debe ser idempotente, tener actor y marca temporal, y no revelar
datos protegidos por el mero hecho de reservar.

## Firma y desbloqueo

| Estado | Condición |
|---|---|
| `unsigned` | ninguna o solo una parte ha aceptado |
| `partially_signed` | una parte ha firmado |
| `contract_signed` | ambas partes han firmado la misma versión |
| `unlocked` | el acceso autorizado a los datos permitidos está habilitado |
| `revoked` | el acceso debe dejar de estar disponible por revocación válida |

`unlocked` requiere autorización server-side. La firma y el consumo deben dejar
traza en `legal_acceptances`, `access_logs` y `ledger` cuando corresponda.

## Operación

Estados abiertos:

`requested` → `agreed` → `in_progress` → `closed`

Estados alternativos terminales:

- `cancelled`: operación cancelada;
- `disputed`: operación en disputa, excluida de pipeline activo y de cerradas.

Una operación `closed` usa únicamente valores contractuales registrados. Las
estimaciones regionales no pueden recalcular operaciones cerradas.

## Reglas de transición

- Toda transición debe indicar actor, origen, destino, motivo y timestamp.
- El backend debe rechazar transiciones imposibles aunque la interfaz las ofrezca.
- Los estados terminales no se reabren sin una acción administrativa documentada.
- La interfaz debe mostrar estado actual, siguiente acción y motivo de bloqueo.
- CRM puede revisar o ejecutar acciones autorizadas, pero no saltarse controles del núcleo.

## Pendientes de confirmación

- Estados reales actualmente persistidos en producción.
- Duración y reglas exactas de cada reserva.
- Qué datos se revelan en cada transición.
- Política de reversión de créditos por expiración, rechazo y cancelación.
- Permisos Staff para cada transición.
