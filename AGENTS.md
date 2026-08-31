# Compra Captación - Sistema de Agentes de Desarrollo y Estándares de Ingeniería

Este documento define la estructura, el flujo operativo y los estándares de ciberseguridad del equipo de agentes especializados para el desarrollo y producción de **Compra Captación** (https://compracaptacion.com/).

## Autoridad operativa vigente

El agente principal vigente es **CAPTACION-2.0**, definido en `.agents/agents/captacion-2.0.md`. Coordina el equipo completo y activa los subagentes según `.agents/activation-matrix.md`. El agente `secretaria` mantiene el backlog y eleva al CEO las tareas bloqueadas o las competencias ausentes. Las tablas históricas de este documento describen agentes existentes, pero la matriz de activación y el perfil CEO son la referencia operativa actual.

---

## 🔒 Regla de Oro de Git y Despliegue en Producción
* **VERSIÓN DE REFERENCIA DORADA (STABLE GOLDEN RELEASE)**: `v1.5.33-stable-production` (Commit: `79a984d`). Todo desarrollo y despliegue futuro debe preservar estrictamente el funcionamiento comprobado en esta versión:
  1. **Rutas Físicas de Scripts**: `assets/js/app.js` y `assets/captacion-app.js` deben existir físicamente en la raíz y devolver siempre `Content-Type: application/javascript` (nunca `text/html`).
  2. **Configuración Global en `<head>`**: `window.CAPTACION_CONFIG` debe inicializarse en el `<head>` antes de cualquier script con encadenamiento seguro (`window.CAPTACION_CONFIG?.basePath || '/'`).
  3. **Exportación Segura a `window`**: Todas las funciones globales (`toggleTheme`, `applyTheme`, `openProfessionalSubscriptionModal`, `openProfessionalAccess`, `initHomeMap`, `setCalculatorRole`, etc.) deben registrarse usando validación de tipo (`typeof fn === 'function'`).
  4. **Modo Claro / Oscuro**: El conmutador de tema debe funcionar fluidamente en desktop y mobile con persistencia en `localStorage ('captacion_theme_v1')`.
  5. **Mapa Leaflet y Enrutador SPA**: El mapa debe inicializarse con datos reales y el panel privado (`/area-privada`) debe responder sin parpadeos.
* **POLÍTICA ESTRICTA**: Ningún desarrollador o agente de IA autónomo debe subir cambios directos a producción ni ejecutar comandos de subida (`git push`, despliegues no autorizados) sin la **autorización explícita y manual del Director del Proyecto**.
* **Gestión de Dependencias**: Es obligatorio el uso de **`pnpm`** (`packageManager: pnpm@9.x`) en todos los subproyectos y utilidades de Node.js para prevenir dependencias fantasma e inyección lateral de librerías.

---

## 👥 Equipo de Agentes (.agents/agents/)

| Agente | Rol / Responsabilidad | Tipo | Modelo | Habilidades Clave (Skills) |
| :--- | :--- | :--- | :--- | :--- |
| **Director** | Coordinador general y consolidador. No ejecuta tareas de bajo nivel. | `mainAgent: true` | `pro` | `creador-de-agentes-captacion`, `saas-metrics`, `requesting-code-review`, `implementing-gdpr-data-protection-controls` |
| **Investigador** | Análisis de mercado inmobiliario B2B en España, MLS y pricing. | `subagent: true` | `flash` | `real-estate-expert`, `real-estate-analyzer`, `saas-revenue-growth-metrics` |
| **Investigador Skill** | Análisis y validación de skills de https://skills.sh/. | `subagent: true` | `flash` | `skill-creator`, `creador-de-agentes-captacion` |
| **Branding** | Naming, propuesta única de valor e identidad visual. | `subagent: true` | `pro` | `copywriting`, `real-estate-expert`, `web-design-guidelines` |
| **Creativo** | Conceptos publicitarios, guiones, copies y campañas. | `subagent: true` | `pro` | `copywriting`, `cold-email`, `email-sequence`, `social-media` |
| **Web** | Landing page comercial, conversión (CRO) y SEO técnico. | `subagent: true` | `pro` | `frontend-design`, `web-design-guidelines`, `seo`, `programmatic-seo` |
| **App Developer** | Desarrollo de Core SPA, Leaflet maps, Stripe webhooks, Base de datos e IA Vera. | `subagent: true` | `pro` | `creador-de-agentes-captacion`, `frontend-design`, `api-design-principles`, `tdd` |
| **Auditor** | Testing funcional E2E, seguridad WAF, transacciones y control de calidad. | `subagent: true` | `pro` | `systematic-debugging`, `requesting-code-review`, `tdd`, `api-design-principles`, `implementing-gdpr-data-protection-controls` |

---

## 🔄 Flujo de Trabajo Oficial (Orquestado por Director)

```mermaid
graph TD
    A[Director: Inicio de Proyecto] --> B[Fase 1: Investigador + Investigador Skill]
    B --> C[Fase 2: Branding & Identidad]
    C --> D[Fase 3: Paralelo]
    D --> E[Creativo: Campañas y Contenido]
    D --> F[Web: Landing Comercial y CRO]
    D --> G[App Developer: Core SPA, DB, Stripe, IA]
    E --> H[Fase 4: Auditoría Integral QA]
    F --> H
    G --> H
    H -->|Detección de Fallos| I[Fase 5: Correcciones por Especialista]
    I --> H
    H -->|Aprobado 100%| J[Fase 6: Consolidación y Entrega Final de Producción]
```

---

## 🛡️ Checklist de Ciberseguridad y Calidad (11 Criterios Obligatorios)

1. **Variables de Entorno**: Credenciales gestionadas mediante variables seguras. Archivo `.env` ignorado en Git y `.env.example` actualizado.
2. **Validación de Entradas (Inputs)**: Sanitización y validación estricta en todos los endpoints (`records.php`, `auth.php`, `credits.php`, etc.) para prevenir SQLi y XSS.
3. **Aislamiento y Prepared Statements en Base de Datos**: Uso exclusivo de sentencias preparadas (`PDO::prepare`) sin concatenación de cadenas SQL.
4. **Autenticación de Rutas**: Restricción estricta de rutas privadas y endpoints protegidos contra accesos no autenticados.
5. **Control de Roles en Servidor (RBAC)**: Validación de permisos en Backend (nunca depender únicamente de la interfaz visual del cliente).
6. **Whitelists de CORS y Cabeceras HTTP Seguras**: Encabezados `X-Content-Type-Options`, `X-Frame-Options`, `Content-Security-Policy` y CORS restrictivo configurados en servidor.
7. **Rate Limiting y Protección Fuerza Bruta**: Límite de intentos en autenticación y peticiones sensibles.
8. **Manejo Seguro de Errores**: Ocultación de trazas físicas, rutas de archivos y detalles internos de base de datos en respuestas JSON.
9. **Uso Obligatorio de `pnpm`**: Árbol de dependencias estricto y enlazado físico para evitar librerías maliciosas o dependencias fantasma.
10. **Auditoría Periódica de Dependencias**: Análisis de vulnerabilidades conocidas (`pnpm audit`).
11. **Exclusión de Datos Sensibles en Logs**: Prohibido registrar contraseñas, tokens de autenticación o información personal identificable (PII) en los logs.
12. **Protección Estricta de Credenciales y Prohibición de Autocompletado en Frontend**: Queda terminantemente prohibido exponer, precargar, sugerir, incrustar o mostrar credenciales, contraseñas en texto claro o botones de acceso rápido con contraseñas en cualquier formulario, modal o vista pública de la web. Todos los formularios de autenticación deben requerir siempre entrada manual y campos limpios.
13. **Estándar de Ingestión Universal de Feeds XML / CRM Inmobiliario**: Todo módulo de importación XML (archivo local, URL remota, previsualización o pasarela CRM) debe implementar estrictamente el pipeline de 6 capas:
    - **Sanitización y Codificación**: Limpieza de BOM UTF-8 (`\xEF\xBB\xBF`), conversión automática de codificaciones legacy (`ISO-8859-1`, `Windows-1252`) a `UTF-8` y sanitización XXE segura preservando nodos CDATA.
    - **Parser Híbrido Polimórfico**: Detección universal de taxonomías (`property`, `inmueble`, `propiedad`, `item`, `oferta`, `vivienda`, `anuncio`), con fallback automático a `DOMDocument` si `SimpleXML` encuentra etiquetas menores sin cerrar.
    - **Aislamiento Estricto de Routers en Inclusiones**: Los archivos requeridos (`auth.php`, `database.php`) deben validar `$isDirectAuthCall` para evitar que intercepten peticiones POST de otros controladores.
    - **Persistencia Atómica y Resiliente**: Sentencias preparadas en transacciones PDO (`beginTransaction` / `commit` / `rollBack`), resolución segura de claves foráneas (`user_id`, `user_email`, `data_origin`) y registro de métricas en `import_batches`.
    - **Blindaje Automático de Datos Ciegos**: Ocultación garantizada de dirección exacta, número, piso, puerta y catastro para proteger las exclusivas.
    - **Captura Global con `Throwable`**: Obligatorio capturar `Throwable` en todos los controladores para devolver siempre respuestas JSON estructuradas (`ok: true/false`) con códigos de estado HTTP precisos (200, 400, 422).

---

## ⚡ Automatización Segura e Integración n8n
* **Webhooks Protegidos**: Recepción con token de autorización Bearer/Header.
* **Respuesta Asíncrona Rápida**: Respuesta 200/201 inmediata al frontend antes de procesar tareas en segundo plano (IA, emails).
* **Gestión Centralizada**: Credenciales administradas exclusivamente desde el gestor de credenciales nativo de n8n.

---

## 🛠️ Habilidades Instaladas en el Proyecto (`.agents/skills/`)
Se encuentran instaladas y disponibles las 26 habilidades modulares del ecosistema:
- `animation-principles`, `api-design-principles`, `azure-ai`, `cold-email`, `copywriting`, `creador-de-agentes-captacion`, `email-sequence`, `firebase-security-rules-auditor`, `firecrawl-seo-audit`, `frontend-design`, `funnel-architect`, `implementing-gdpr-data-protection-controls`, `marketing-automation`, `programmatic-seo`, `real-estate-analyzer`, `real-estate-content-production`, `real-estate-expert`, `requesting-code-review`, `saas-metrics`, `saas-revenue-growth-metrics`, `seo`, `skill-creator`, `social-media`, `systematic-debugging`, `tdd`, `web-design-guidelines`.
