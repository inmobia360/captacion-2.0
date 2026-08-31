# Estado Actual del Proyecto - Compra Captación

## 🚀 Estado de Producción
* **Servidor**: Hostinger Cloud / VPS (`147.79.103.72`)
* **Dominio**: https://khaki-parrot-519933.hostingersite.com/ (https://compracaptacion.com/)
* **Batería de Pruebas E2E**: **18/18 tests PASSED (100%)** en `/tests/run_tests.php`

## 📋 Hitos Implementados y Verificados
1. **Frontend & UX**:
   - Eliminación total del término *"puenteo"* sustituido por lenguaje B2B profesional ("Privacidad y Protección Registral", "Colaboración 50/50").
   - Jerarquía tipográfica balanceada (`font-bold` / `font-semibold`, interlineados relajados, contrastes suaves).
   - Asistente IA Vera corregido con control de apertura/cierre, botón ✕ y estado oculto por defecto (`display: none !important;`).
   - Modo claro / modo oscuro adaptativo con persistencia y actualización reactiva de mapas Leaflet.
   - Eliminación de fugas de CSS en la cabecera del documento.
2. **Backend & Base de Datos**:
   - Inicialización automática de tablas: `users`, `records`, `wallets`, `ledger`, `access_logs`, `operations`, `payments`, `saved_records`, `notifications`, `reports`, `audit_logs`, `legal_acceptances`.
   - Lógica financiera 50/50 y monedero de créditos de bienvenida.
3. **Estándares de Agentes**:
   - Integración completa de la plantilla de desarrollo, sincronización `AGENTS.md` y `CLAUDE.md`, y adopción de `pnpm`.
