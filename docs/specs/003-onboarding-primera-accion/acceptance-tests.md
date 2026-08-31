# Pruebas de aceptación — Spec 003

## Datos sintéticos

| Fixture | Estado | Plan | Capacidades |
|---|---|---|---|
| `user_base_ok` | verified | base | ninguna premium |
| `user_pending` | pending_verification | base | ninguna premium |
| `user_premium_ok` | verified | advanced/active | `full_tools`, `ai_assistance` |
| `user_premium_unpaid` | verified | advanced/inactive | ninguna premium |
| `user_suspended` | suspended | cualquiera | ninguna |
| `user_staff` | staff_active | internal | CRM según RBAC |

## Casos

| ID | Dado | Cuando | Entonces |
|---|---|---|---|
| ONB-01 | visitante sin sesión | abre onboarding | no se muestra contenido privado; se ofrece acceso público/registro |
| ONB-02 | `user_base_ok` | entra por primera vez | ve plan base, saldo real y CTAs publicar/buscar |
| ONB-03 | `user_pending` | entra por primera vez | ve qué falta para verificar y no recibe acceso premium |
| ONB-04 | `user_premium_ok` | entra por primera vez | ve plan avanzado y acceso a `pro`, `full_tools` e IA |
| ONB-05 | `user_premium_unpaid` | intenta entrar en `pro` | se bloquea la sesión premium y se ofrece revisar activación |
| ONB-06 | `user_suspended` | intenta entrar en cualquier área privada | se rechaza el acceso y se ofrece soporte |
| ONB-07 | `user_staff` | abre la entrada Staff | recibe CTA CRM; no se mezcla con onboarding profesional |
| ONB-08 | respuesta de créditos sin disponibilidad | carga onboarding | no aparece saldo inventado; se muestra reintento |
| ONB-09 | respuesta de entitlements incompleta | carga onboarding premium | no se conceden capacidades por defecto |
| ONB-10 | usuario ya completó onboarding | vuelve a entrar | ve estado resumido o destino pendiente, sin repetir alta |
| ONB-11 | usuario selecciona publicar | pulsa CTA principal | conserva sesión y navega a publicación sin crear duplicado |
| ONB-12 | usuario selecciona buscar | pulsa CTA secundaria | conserva sesión y navega a búsqueda sin crear duplicado |
| ONB-13 | red interrumpe navegación | reintenta | el resultado no duplica sesión ni registro |
| ONB-14 | se revoca pago durante sesión | solicita `full_tools` | backend deniega capacidad y la interfaz actualiza estado |

## Criterio de aprobación

Todos los casos deben ejecutarse con fixtures sintéticos. Los casos ONB-04,
ONB-05 y ONB-14 deben comprobarse además en backend, no solo visualmente.
