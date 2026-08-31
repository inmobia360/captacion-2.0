# Referencia de producción — 24/08/2026

## Alcance observado

Revisión de solo lectura realizada sobre:

- `https://compracaptacion.com/`
- `https://compracaptacion.com/crm/`

La revisión se realizó el 24/08/2026 y sirve como fotografía funcional de referencia antes de cualquier despliegue.

## Aplicación principal

Título observado: `Compra Captación | Plataforma de Colaboración entre Profesionales Inmobiliarios`.

Elementos visibles confirmados:

- Navegación de Inicio, Oportunidades, Cómo funciona, Precios y Recursos y Contratos.
- Acceso a creación de cuenta e inicio de sesión profesional.
- Mensaje comercial de colaboración y reparto 50/50.
- Calculadora de honorarios con porcentaje inicial del 3 % y selección de porcentajes.
- Mapa de estimaciones regionales de honorarios.
- Captaciones visibles, demandas activas, zonas activas y coincidencias de venta.
- Mapa interactivo de oportunidades con protección de direcciones exactas.
- Acceso al panel privado `/area-privada`.
- Widget de Vera visible en la aplicación pública.

Valores que la página pública mostraba durante la revisión:

- 6 captaciones visibles y 4.850.000 € en volumen.
- 4 demandas activas y 2.150.000 € de presupuesto.
- 5 cruces y 1.850.000 € en operaciones.

Estos valores son una fotografía de producción, no una autorización para usarlos como datos de prueba ni como fuente de cálculo del panel privado.

## CRM

URL observada: `https://compracaptacion.com/crm/`.

Título: `CRM Compra Captación | Suite de Captación Inmobiliaria`.

Estado observado:

- Portal interno de operaciones Staff HQ.
- Pantalla de acceso protegida.
- Campos de correo corporativo y contraseña.
- Botón de acceso al Panel de Operaciones.
- Recuperación de contraseña y solicitud de cuenta Staff visibles.
- No se accedió ni se intentó acceder con credenciales.

## Punto de restauración

El repositorio local contiene:

- Tag dorado: `v1.5.33-stable-production`.
- Commit dorado: `79a984d`.
- HEAD local durante la revisión: `07a5f15`.

No se ha demostrado que el HEAD local sea exactamente la versión actualmente servida por `compracaptacion.com`. Por ello, el punto de restauración técnico seguro conocido es `v1.5.33-stable-production` (`79a984d`), sujeto a conservar también una copia del contenido y de la base de datos de producción antes de cualquier cambio.

## Estado del workspace

Durante esta fotografía había cambios locales no desplegados:

- `assets/captacion-app.js` modificado.
- `docs/13-definiciones-metricas-panel-ejecutivo.md` añadido.
- Este documento de referencia añadido.

No se ejecutó ninguna acción remota, login, modificación, subida ni despliegue.
