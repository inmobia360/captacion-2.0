# Arquitectura del Sistema - Compra Captación

## 1. Visión General
Compra Captación es una plataforma B2B en producción alojada en **Hostinger** para colaboración inmobiliaria profesional en España. Permite a agentes inmobiliarios, agencias y captadores compartir inmuebles con datos ciegos de protección registral, cruzar demandas y formalizar acuerdos de honorarios al 50/50.

## 2. Stack Tecnológico
* **Frontend**: Single Page Application (SPA) nativa en Vanilla JavaScript modular (`assets/js/app.js`), renderizado ultrarrápido sin sobrecarga de frameworks, Tailwind CSS CDN y mapas interactivos Leaflet.js.
* **Backend**: PHP 8.x optimizado con arquitectura RESTful (`api/auth.php`, `api/records.php`, `api/credits.php`, `api/stripe.php`, `api/territories.php`).
* **Base de Datos**: PDO con soporte híbrido SQLite (`data/captacion.sqlite`) y MySQL Hostinger con migraciones automáticas y sentencias preparadas.
* **Pagos y Créditos**: Pasarela Stripe para suscripciones profesionales y packs de créditos con ledger financiero (`wallets` / `ledger`).
* **IA Vera**: Asistente inmobiliaria conversacional conectada mediante pasarela BYO-AI y modelos Llama-3.3-70B / OpenRouter.
* **Automatización**: Integración de webhooks seguros para flujos n8n y Mailchimp.

## 3. Seguridad y Protección de Datos
* **Datos Ciegos**: La dirección exacta y datos del propietario permanecen cifrados/ocultos hasta que ambas partes validan el acuerdo de colaboración.
* **WAF y Sanitización**: Validación estricta con `filter_var`, `htmlspecialchars` y PDO Prepared Statements.
* **Control de Acceso**: Sesiones autenticadas, nonces y RBAC en servidor.
