# Inventario T1 — rutas, CTA y estados

## Web pública / aplicación principal

| Etapa | Ruta o acción | Estado esperado | Siguiente acción |
|---|---|---|---|
| Descubrimiento | `/inicio` | visitante conoce propuesta 50/50, datos ciegos y créditos | empezar gratis o explorar oportunidades |
| Exploración | `#/oportunidades` | oportunidades visibles con datos protegidos | abrir una oportunidad o iniciar sesión |
| Demandas | `#/buscar-captaciones` | demandas activas y filtros | publicar captación o buscar coincidencia |
| Publicación | `#/publicar` / `#/publicar-demanda` | formulario y previsualización | publicar o corregir datos |
| Cuenta | `#/panel` / acceso profesional | sesión, verificación y perfil | completar perfil y entrar al área privada |
| Área privada | `/area-privada` | panel con cartera, demandas, créditos y operaciones | ejecutar primera acción |
| Planes | `#/planes-premium` | plan, créditos y activación | comprar/activar según permisos |
| Recursos | `/recursos` | contratos y material de apoyo | descargar o volver al flujo |

## CRM

| Etapa | Ruta/hash | Estado esperado | Siguiente acción |
|---|---|---|---|
| Gatekeeper | `crm.compracaptacion.com/` | acceso Staff protegido | iniciar sesión, recuperar acceso o solicitar cuenta |
| Resumen | `#resumen` | KPIs de usuarios, cartera, finanzas y tickets | abrir módulo relevante |
| Inmuebles | `#inmuebles` | cartera y demandas administrables | revisar, cambiar estado o auditar |
| Tickets | `#tickets` | soporte abierto y estados | atender o cerrar ticket |
| Usuarios | `#usuarios` | usuarios, agencias, roles y saldos | revisar permisos o perfil |
| XML | módulo `xml` | feeds y lotes de importación | revisar calidad y errores |
| Finanzas | módulo `finance` | créditos, pagos y ledger | auditar movimientos |
| Telemetría | módulo `telemetry` | eventos y salud operativa | investigar anomalías |

## Área profesional / premium

La existencia del subdominio `pro.compracaptacion.com` está confirmada por el
propietario, pero sus rutas y estados internos quedan pendientes de captura
directa. Debe validarse contra el repositorio premium y la superficie desplegada.

## Hallazgos de recorrido

- La aplicación principal ya tiene dos modelos de navegación: rutas limpias y
  hash SPA; deben consolidarse en un contrato de navegación.
- El primer CTA público ofrece valor inmediato, pero el siguiente paso del
  onboarding debe estar definido como evento y no solo como pantalla.
- El CRM expone módulos operativos diferenciados; esos módulos deben compartir
  identidad de producto, pero conservar autorización Staff independiente.
- Las cifras públicas y los KPIs privados deben declararse como datos reales,
  estimaciones o ejemplos para evitar confusión.
