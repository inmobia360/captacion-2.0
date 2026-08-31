# 3. Flujos Completos y Modelo de Negocio

## 3.1 Modalidad 1: Colaboración 50/50 de Honorarios
1. **Publicación con Auto-Publish**: El agente captador publica el inmueble con datos públicos visibles (zona, precio, características) y datos sensibles protegidos (dirección exacta, contacto del propietario).
2. **Búsqueda y Matching**: Un agente colaborador con comprador cualificado localiza la captación en el marketplace.
3. **Reserva protegida (1 Crédito)**: El agente reserva 1 crédito durante 72 horas. La reserva no revela teléfonos, emails ni dirección exacta y se libera si la colaboración no continúa.
4. **Doble firma server-side**: Cada parte firma la misma versión documental mediante su sesión autenticada. Solo cuando `captador_signed` y `colaborador_signed` son verdaderos se marca `contract_signed` y se habilita el acceso autorizado.
5. **Consumo definitivo**: El crédito pasa de reservado a consumido únicamente con una reserva activa aceptada. El ledger y `access_logs` registran la operación con idempotencia.

## 3.2 Modalidad 2: Venta o Cesión 100% de la Operación
- Cede íntegramente la hoja de encargo a otra agencia a cambio del 100% de la comisión pactada o un fee fijo de traspaso.
