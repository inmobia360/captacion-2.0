# Contexto de producción — 2026-08-31

## Superficies declaradas

- Web pública: `https://compracaptacion.com/inicio`
- Área premium avanzada: `https://pro.compracaptacion.com/`
- CRM operativo: `https://crm.compracaptacion.com/#resumen`

Estas tres superficies se consideran parte del mismo producto desplegado y deben
tratarse como contratos de producto distintos pero coordinados.

## Verificación pública realizada

En la web pública se observaron:

- propuesta B2B de colaboración entre profesionales inmobiliarios;
- reparto estándar 50/50 y trazabilidad documental;
- datos ciegos y protección de dirección/datos del propietario;
- registro gratuito con 3 créditos válidos durante 30 días;
- asistente Vera;
- calculadora de honorarios y mapa territorial;
- marketplace de captaciones y demandas;
- mapa nacional de oportunidades;
- acceso a oportunidades, demandas, coincidencias, precios y recursos.

También se observó que la aplicación presenta cifras operativas visibles y que
estas deben distinguirse siempre entre datos reales, estimaciones y ejemplos
comerciales antes de reutilizarlas en documentación o pruebas.

## Rol de cada superficie

| Superficie | Rol | Contrato que debe protegerse |
|---|---|---|
| `compracaptacion.com` | Adquisición, explicación del producto y marketplace público | SEO, navegación pública, datos ciegos y conversión |
| `pro.compracaptacion.com` | Superficie premium para usuarios con plan avanzado de pago | sesión, entitlement de plan, `full_tools`, asistencia IA y datos privados autorizados |
| `crm.compracaptacion.com` | Operación interna exclusiva Staff HQ | RBAC Staff, administración, auditoría y soporte; nunca usuarios públicos |

## Implicaciones SDD

Cada superficie tendrá specs propias, pero compartirán una constitución común y
contratos explícitos para autenticación, usuario, plan, créditos, oportunidades,
operaciones y permisos. Ninguna interfaz será autoridad para conceder acceso,
saldo o estado financiero.

## Verificación pendiente

- Inspeccionar sin credenciales el DOM, recursos y rutas de `pro.compracaptacion.com`.
- Inspeccionar sin credenciales el acceso y la pantalla inicial del CRM.
- Confirmar qué repositorio y commit corresponden a cada dominio.
- Comparar las métricas visibles con datos reales y documentación de producción.
