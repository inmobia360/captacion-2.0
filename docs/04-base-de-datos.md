# 4. Diseño de Base de Datos e Integridad

## 4.1 Esquema de Tablas
- `users`: Profesionales inmobiliarios, CIF/NIF, agencias, estado de verificación, roles y hash bcrypt.
- `records`: Captaciones e inmuebles demandados, geolocalización, comisiones, datos públicos y privados.
- `wallets`: Saldo disponible, consumido y pendiente de cada usuario.
- `ledger`: Libro contable inmutable con tipo de movimiento, importe, saldo resultante e `idempotency_key`.
- `access_logs`: Registro inmutable de desbloqueos de información sensible y consumo de créditos.
- `operations`: Ciclo de vida de transacciones 50/50 y 100% (negociación, acuerdo, señal, notaría, liquidación).
- `payments`: Historial de transacciones de Stripe Checkout y recargas.
- `legal_acceptances`: Aceptación de términos y NDAs con IP y sellado de tiempo.
