# Contrato de idempotencia v1 — reservas, créditos y operaciones

## Objetivo

Garantizar que repetir una petición por doble clic, timeout, refresh o reintento
de red no produzca dos efectos de negocio.

## Operaciones protegidas

| Operación | Clave mínima | Resultado duplicado |
|---|---|---|
| crear reserva | usuario + oportunidad + `idempotency_key` | devuelve la reserva original |
| consumir crédito | usuario + reserva + `idempotency_key` | devuelve el movimiento original |
| desbloquear datos | reserva + usuario + `idempotency_key` | no vuelve a revelar ni cobrar |
| recompensa al captador | evento de desbloqueo + beneficiario | un único abono |
| Checkout/pago | evento Stripe + referencia externa | un único pago y ledger |
| firma documental | usuario + documento + versión | conserva la firma original |
| cambio de estado | recurso + transición + petición | no duplica auditoría material |

## Reglas

1. El cliente genera una clave opaca única por intención de operación.
2. El servidor valida usuario, recurso, permisos y formato antes de consultar la clave.
3. La clave se guarda junto con el resultado dentro de la misma transacción.
4. Si la clave ya existe, se devuelve el resultado persistido sin repetir efectos.
5. Una misma clave con parámetros incompatibles se rechaza como conflicto.
6. El timeout del cliente no implica fallo del servidor; debe permitirse reintentar con la misma clave.
7. Las operaciones financieras actualizan wallet, ledger, auditoría y estado de forma atómica.
8. Las claves no deben contener PII, contraseñas ni tokens reutilizables.

## Respuestas API

```json
{
  "ok": true,
  "idempotent": false,
  "operation_id": "opaque-id",
  "status": "reserved"
}
```

En un reintento válido, `idempotent` será `true` y el resultado será el mismo.
Una colisión con parámetros distintos devolverá error estructurado de conflicto
sin revelar información de otro usuario.

## Pruebas mínimas

- dos peticiones simultáneas con la misma clave;
- timeout seguido de reintento;
- misma clave con cuerpo diferente;
- clave reutilizada por otro usuario;
- fallo antes de commit;
- fallo después de commit y antes de responder;
- webhook Stripe repetido;
- recompensa repetida por el mismo desbloqueo.

## Pendientes

- Confirmar nombres de tablas e índices disponibles en producción.
- Confirmar si `ledger.idempotency_key` es único globalmente o por usuario.
- Definir retención de claves y política de limpieza.
- Ejecutar pruebas en entorno controlado con datos sintéticos.
