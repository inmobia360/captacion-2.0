# Matriz de activación de CAPTACION-2.0

El CEO activa el conjunto mínimo de agentes según la fase y el riesgo. La secretaria mantiene el seguimiento y el auditor puede solicitar nuevos especialistas.

## Fases

### Fase 0 — Contexto y SDD

`secretaria` + `research-insights` + `real-estate-expert` + `CAPTACION-2.0`

### Fase 1 — Diseño técnico

`app-developer` + `data-architecture-migrations` + `security-privacy` + `real-estate-compliance`

### Fase 2 — UX y adquisición

`accessibility-ux` + `conversion-cro` + `copywriting` + `seo` + `content-distribution`

### Fase 3 — Core funcional

`app-developer` + `quality-assurance-e2e` + `security-privacy` + `product-analytics`

### Fase 4 — Pagos, IA y red

`payments-entitlements` + `ai-vera-safety` + `referral-growth` + `network-product` + `product-analytics`

### Fase 5 — CRM y operación

`crm-operations` + `customer-success` + `security-privacy` + `quality-assurance-e2e`

### Fase 6 — Preparación de despliegue

`release-devops` + `data-architecture-migrations` + `performance-observability` + `quality-assurance-e2e` + `auditor`

## Reglas de activación por riesgo

- Datos personales o permisos: activar `security-privacy`.
- Créditos, Stripe o recompensas: activar `payments-entitlements` y `quality-assurance-e2e`.
- Vera o información legal/financiera: activar `ai-vera-safety` y `real-estate-compliance`.
- CRM: activar `crm-operations` y `security-privacy`.
- Cambio de esquema: activar `data-architecture-migrations`.
- Push, despliegue o DNS: activar `release-devops` y exigir autorización explícita.
- Cambio de interfaz: activar `accessibility-ux`.

## Cierre de fase

Una fase solo se cierra cuando existe spec actualizada, evidencia de pruebas, revisión del auditor y decisión documentada del CEO.
