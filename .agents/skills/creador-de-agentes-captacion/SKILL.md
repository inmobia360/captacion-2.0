---
name: creador-de-agentes-captacion
description: >-
  Habilidad y agente maestro especializado en el diseño, arquitectura, desarrollo,
  seguridad y despliegue en producción del ecosistema Compra Captación (Captacion.app).
  Utiliza esta habilidad para generar especificaciones técnicas, prompts de desarrollo,
  contratos de API, esquemas de bases de datos y validaciones de producción.
---

# Creador de Agentes Captación

## Propósito y Rol
Esta habilidad dota al agente del conocimiento integral de ingeniería, arquitectura de software, modelo de datos y reglas de negocio del ecosistema **Compra Captación** (https://compracaptacion.com/).

Su objetivo principal es actuar como **Arquitecto de Software y Creador de Agentes Técnicos**, garantizando que cualquier desarrollo, réplica o extensión del proyecto se ejecute con máxima seguridad, alto rendimiento y sin deuda técnica.

---

## 1. Arquitectura Técnica del Ecosistema

### A. Núcleo de Aplicación (SPA / Web App)
- **Tema Base**: `Compra Captación` (versión funcional 1.5.32+).
- **Plantilla Principal**: `template-app-interactiva.php` (Single Page Application desacoplada).
- **Diseño & UI**: Tailwind CSS compilado, tipografía escalable WCAG 2.2, soporte nativo de modo claro/oscuro persistente (`captacion_theme_v1` en `localStorage`).
- **Constructores Web**: **Prohibido el uso de constructores pesados (Elementor, Divi)** en el núcleo interactivo. El frontend se mantiene reactivo con JavaScript modular y componentes limpios para evitar bloqueos del DOM y sobrecarga de consultas.

### B. Módulos Funcionales
1. **Marketplace de Inmuebles y Demandas**:
   - Captaciones de venta y alquiler con galería de imágenes optimizadas.
   - Demandas activas de compradores con rangos presupuestarios y zonas objetivo.
   - Motor de cruce inteligente (matching automático demanda-propiedad).
2. **Geolocalización & Mapas**:
   - Mapas interactivos con **Leaflet.js**.
   - Base de datos geográfica española estructurada (`src/data/territorios-espana.json`).
   - Clusterización de marcadores y filtros de radio kilométrico.
3. **Economía de Plataforma (Créditos y Planes)**:
   - Registro de transacciones en tabla dedicada `wp_captacion_credits_ledger` (balance disponible, consumos, bonificaciones).
   - Pasarela de pago **Stripe** mediante Checkout Sessions y **Webhooks firmados e idempotentes**.
   - Sistema de referidos y recompensas por validación de operaciones.
4. **Inteligencia Artificial ("Vera")**:
   - Asistente conversacional inmobiliario (`api-vera.php`).
   - Conexión con servidor de inferencia VPS propio (modelos Ollama / Qwen).
   - Parámetros configurables vía `wp-config.php` (`CAPTACION_VERA_INFERENCE_URL`, `CAPTACION_VERA_INFERENCE_MODEL`).
5. **Panel Maestro de Administración (`captacion-master-core`)**:
   - Acceso administrativo aislado e independiente del login tradicional de WordPress.
   - Gestión de acuerdos de confidencialidad (NDA) y contratos de honorarios compartidos.

---

## 2. Directivas de Base de Datos y Rendimiento

1. **Evitar la saturación de `wp_postmeta`**:
   - Las búsquedas geoespaciales y filtros masivos deben apoyarse en índices adecuados y taxonomías estructuradas.
   - El historial de créditos y movimientos financieros jamás se almacena en metadatos volátiles; se utiliza una tabla transaccional con integridad ACID.
2. **Caché y Optimización (LiteSpeed Cache)**:
   - Excluir de la caché de páginas completas las rutas dinámicas (`/panel`, `#/panel`, `wp-json/captacion/v1/*`).
   - Minificación y carga diferida (defer) de librerías externas.

---

## 3. Protocolo de Seguridad

- **Validación de Nonces**: En todas las peticiones AJAX y REST.
- **Sanitización y Escape**: `sanitize_text_field`, `esc_html`, consultas parametrizadas con `$wpdb->prepare()`.
- **Protección WAF**: Integración limpia con cortafuegos de aplicación sin solapamiento de plugins redundantes.
- **Cero secretos en código**: Claves de Stripe, endpoints de IA y contraseñas de administración deben inyectarse exclusivamente por variables de entorno o `wp-config.php`.

---

## 4. Instrucciones para la Generación de Prompts de Desarrollo

Cuando este agente diseñe un prompt para un desarrollador o agente ejecutor, debe estructurarlo obligatoriamente con:
1. **Contexto del Proyecto y Objetivo de Producción**.
2. **Especificación del Stack y Requisitos de Servidor**.
3. **Estructura de Archivos y Responsabilidad de cada Componente**.
4. **Esquema de Base de Datos y Contratos de Endpoints REST/AJAX**.
5. **Flujo de Pagos Stripe & Webhooks**.
6. **Checklist de Calidad y Pruebas E2E (Definition of Done)**.
