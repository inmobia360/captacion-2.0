# 2. Arquitectura y Stack de Producción

## 2.1 Componentes del Stack
- **Servidor Web**: LiteSpeed Web Server sobre Linux en Hostinger (`https://khaki-parrot-519933.hostingersite.com/`).
- **Motor de Backend**: PHP 8.3.31 nativo con extensiones `pdo_sqlite`, `curl`, `openssl`, `mbstring`.
- **Base de Datos**: SQLite 3 con WAL (Write-Ahead Logging) para concurrencia masiva y cero latencia, con abstracción extensible a MySQL/PostgreSQL.
- **Frontend SPA**: HTML5, Tailwind CSS (`captacion-tailwind.css`), Vanilla JS reactivo (`assets/js/app.js`), Leaflet Map Engine con CartoDB tiles y Lucide Icons.
- **Pasarela de Pagos**: Stripe API Checkout (v3) y Webhooks idempotentes.
- **Motor de Inteligencia Artificial**: Asistente Vera impulsada por el modelo `llama-3.3-70b-versatile`.
