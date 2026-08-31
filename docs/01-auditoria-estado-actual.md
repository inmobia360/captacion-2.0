# 1. Auditoría del Estado Actual de Compra Captación

## 1.1 Estructura del Proyecto Auditado
Se inspeccionó exhaustivamente el código base existente en `D:\CAPTACION.APP` y sus componentes principales:
- **Release base**: `captacion-app-1.5.32-functional-20260815-v4`
- **Plugin de créditos y transacciones**: `captacion-credits-core-0.1.11.2-functional-20260815-v4`
- **Media y creativos**: Ubicados en `C:\Users\ernes\Pictures\compracaptacion_media`
- **Dataset territorial oficial**: `territorios-espana.json` con 8.131 municipios y 52 provincias de España (fuente INE y Cartociudad).

## 1.2 Hallazgos Técnicos y Vulnerabilidades Mitigadas
1. **Acoplamiento WordPress eliminado**: Se implementó una arquitectura modular desacoplada con backend PHP 8.3 REST nativo, compatible tanto de forma autónoma con LiteSpeed como integrado con WordPress si fuera requerido.
2. **Protección de Datos Sensibles**: Se implementó un enmascaramiento estricto en `api/records.php` para que usuarios anónimos o no autorizados nunca reciban datos privados de propietarios (dirección exacta, teléfono de captación o notas confidenciales).
3. **Persistencia Contable de Doble Entrada**: Monedero virtual con libro contable (*ledger*) inmutable e idempotencia mediante hash único.
