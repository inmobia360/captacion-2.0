# Análisis de ampliación del equipo CAPTACION-2.0

## Diagnóstico

El equipo actual cubre producto general, Real Estate, SEO, copy, social media, desarrollo, auditoría, atención y analítica. El proyecto, sin embargo, ya contiene varios sistemas con riesgos y decisiones propias: tres superficies con audiencias distintas, sesiones y capacidades, publicaciones ciegas, matching, créditos, Stripe, firma/documentos, XML, IA Vera, RGPD, referidos, diagnósticos profesionales y un bucle viral.

La carencia principal no es añadir más perfiles de marketing, sino separar responsabilidades críticas que ahora recaen sobre App Developer, Auditor o CEO.

## Agentes recomendados para incorporar

### Prioridad P0 — antes de una integración grande o producción

#### 1. `security-privacy`

Especialista en seguridad de aplicaciones, RGPD/LOPDGDD/LSSI y privacidad por diseño. Revisa sesiones, tokens, datos ciegos, XSS/SQLi, exposición de PII, retención, exportación/supresión y límites entre público, Pro y CRM.

**Necesidad observada:** el proyecto tiene endpoints de autenticación, publicaciones, créditos, XML, dossiers, diagnósticos y CRM. Una auditoría genérica no sustituye una revisión de privacidad y threat model.

#### 2. `payments-entitlements`

Especialista en Stripe, webhooks, suscripciones, planes, créditos, reservas, reembolsos y entitlements server-side.

**Necesidad observada:** existen compras, monederos, ledger, reservas, referidos y plan avanzado. Debe decidir qué significa exactamente “advanced activo”, “full_tools” y “AI assistance”, y comprobar reconciliación e idempotencia.

#### 3. `data-architecture-migrations`

Especialista en esquema, migraciones, compatibilidad SQLite/MySQL, índices, backups y consistencia transaccional.

**Necesidad observada:** la inicialización de tablas está embebida en `api/database.php`, conviven datos y variantes históricas, y el entorno local no dispone de driver PDO. Esto requiere una estrategia de migración y rollback, no solo nuevos endpoints.

#### 4. `release-devops`

Especialista en entornos, configuración PHP, despliegue, DNS/subdominios, backups, observabilidad y rollback.

**Necesidad observada:** public, Pro y CRM deben desplegarse con límites de acceso y configuraciones diferentes. La habilitación de `pdo_sqlite`/`pdo_mysql` ya está pendiente para despliegue.

#### 5. `ai-vera-safety`

Especialista en producto IA, prompts, grounding, privacidad, límites de asesoramiento y evaluación de respuestas.

**Necesidad observada:** Vera interviene en onboarding, ACM, matching, redacción, legal y soporte. Debe evitar presentar asesoramiento jurídico/financiero como certeza y escalar correctamente a profesionales humanos.

#### 6. `quality-assurance-e2e`

Especialista en pruebas de recorrido completo y contratos API, separado del auditor de seguridad.

**Necesidad observada:** el flujo real atraviesa registro → créditos → publicación → matching → reserva → firma → desbloqueo. La validación sintáctica no comprueba esa cadena.

### Prioridad P1 — para el MVP de viralidad y crecimiento

#### 7. `referral-growth`

Diseña enlaces compartibles, tokens opacos, atribución, invitado activo, recompensas bilaterales y controles antifraude.

**Necesidad observada:** la Spec 005 requiere diferenciar registro vacío de interacción útil y evitar autorreferidos, duplicados y recompensas dobles.

#### 8. `network-product`

Diseña “Mi Red”, conexiones, disponibilidad, profesionales, matches y colaboraciones sin convertirlo en un ranking prematuro.

**Necesidad observada:** el efecto de red es una parte central de la visión, pero todavía faltan modelo de relación, visibilidad y permisos.

#### 9. `conversion-cro`

Especialista en funnels, onboarding, CTA, activación y experimentación controlada.

**Necesidad observada:** hay múltiples rutas, modales, créditos de bienvenida y CTAs. Hace falta medir y reducir la dispersión entre intención y primera acción útil.

#### 10. `content-distribution`

Especialista en tarjetas profesionales, WhatsApp, LinkedIn, Facebook, metadatos Open Graph y contenido compartible sin PII.

**Necesidad observada:** la viralidad propuesta depende de que una oportunidad sea comprensible y compartible sin revelar datos sensibles.

### Prioridad P2 — para robustez de producto

#### 11. `crm-operations`

Especialista en operaciones internas, roles Staff, incidencias, moderación, revisión de publicaciones, soporte y trazabilidad en CRM.

**Necesidad observada:** CRM es staff-only y debe tener workflows propios, no ser una extensión de la navegación pública.

#### 12. `real-estate-compliance`

Especialista en práctica inmobiliaria española, encargos, exclusivas, honorarios, colaboración 50/50, publicidad y escalado jurídico.

**Necesidad observada:** el agente Real Estate aporta negocio; este perfil revisa si los estados y documentos representan correctamente la práctica profesional y sus límites.

#### 13. `accessibility-ux`

Especialista en WCAG, teclado, contraste, formularios, lenguaje claro, móvil y estados de error.

**Necesidad observada:** la aplicación es una SPA grande con modales, formularios progresivos, mapas y múltiples estados; la accesibilidad no puede quedar implícita en diseño general.

#### 14. `performance-observability`

Especialista en Core Web Vitals, PHP, consultas, carga de assets, logs, errores y métricas técnicas.

**Necesidad observada:** hay una superficie pública SEO y paneles ricos con mapas, IA, XML y documentos. Rendimiento y trazabilidad deben medirse por superficie.

#### 15. `research-insights`

Especialista en investigación de usuarios y mercado, entrevistas, síntesis de evidencia y priorización de puntos de dolor.

**Necesidad observada:** ya existe una capa de “voice of real estate”; este agente evita convertir hipótesis de investigación en requisitos no validados.

## Agentes que no conviene crear todavía

- `ranking-gamification`: esperar a tener actividad suficiente y métricas de calidad.
- `ambassador-program`: depende de densidad territorial y antifraude.
- `social-automation`: no automatizar envíos externos hasta cerrar consentimiento y límites.
- `legal-advisor-autonomous`: el agente puede preparar preguntas y detectar riesgos, pero no sustituir revisión jurídica.
- `growth-hacker-generalist`: duplicaría funciones de SEO, CRO, referrals y analytics sin un contrato claro.

## Equipo recomendado por fase

### Fase actual: consolidación SDD y núcleo seguro

`CAPTACION-2.0` + `app-developer` + `security-privacy` + `data-architecture-migrations` + `quality-assurance-e2e` + `real-estate-compliance`.

### Fase MVP viral

Añadir `referral-growth` + `network-product` + `conversion-cro` + `content-distribution` + `product-analytics`.

### Fase de producción

Añadir `payments-entitlements` + `release-devops` + `ai-vera-safety` + `crm-operations` + `performance-observability` + `accessibility-ux`.

## Regla de activación

El CEO no debe activar todos los agentes en cada iteración. Debe seleccionar el conjunto mínimo según el riesgo:

```text
Cambio de UI        → UX/CRO + QA
Cambio de API       → App + Seguridad + QA
Créditos/Stripe     → Payments + Data + Seguridad + QA
Datos personales    → Privacy + Real Estate Compliance + QA
Vera/IA             → AI Safety + Privacy + Real Estate
SEO/viralidad       → SEO + Copy + Referral + Analytics
Despliegue          → DevOps + Data + Auditor + QA
CRM                 → CRM Operations + Privacy + Security
```

## Conclusión

La incorporación más importante no es un agente creativo adicional. Es crear una segunda línea de control técnico y operativo alrededor de datos, pagos, IA y despliegue. Recomiendo empezar por los seis agentes P0 y activar los de viralidad únicamente cuando la Spec 005 entre en implementación.
