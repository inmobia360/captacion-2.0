# 5. Seguridad, WAF y Auditoría

## 5.1 Medidas de Seguridad Implementadas
1. **Consultas Parametrizadas (PDO Prepared Statements)**: Inmunidad total contra SQL Injection.
2. **Protección XSS**: Sanitización de inputs con `htmlspecialchars` y filtrado estricto de URLs con `FILTER_SANITIZE_URL`.
3. **Cabeceras de Seguridad HTTP**: X-Content-Type-Options: nosniff, X-Frame-Options: SAMEORIGIN, X-XSS-Protection: 1; mode=block.
4. **Auditoría de Acceso**: Registro de IP, User-Agent y timestamp en cada desbloqueo y operación financiera.
