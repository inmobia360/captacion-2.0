# Contrato funcional de onboarding v1

## Estado de pantalla

| Estado | Mensaje principal | CTA primaria | CTA secundaria |
|---|---|---|---|
| `verified_base` | Tu cuenta está lista para empezar | Publicar una captación | Buscar una oportunidad |
| `pending_verification` | Completa la verificación para ampliar el acceso | Completar perfil | Explorar funciones disponibles |
| `premium_active` | Tu espacio avanzado está activo | Entrar en `pro` | Publicar o buscar |
| `premium_payment_pending` | Tu plan avanzado necesita confirmación de pago | Revisar activación | Volver al área base |
| `premium_expired` | Tu acceso avanzado ha terminado | Revisar plan | Continuar con funciones base |
| `suspended` | Tu cuenta está temporalmente restringida | Contactar con soporte | Cerrar sesión |
| `service_unavailable` | No podemos comprobar tu estado ahora | Reintentar | Continuar solo con contenido público |
| `staff_only` | Acceso reservado al equipo interno | Entrar en CRM | Volver |

## Composición de la pantalla

1. Saludo y nombre solo si procede del perfil autorizado.
2. Estado de cuenta con etiqueta comprensible.
3. Plan y estado de pago.
4. Créditos disponibles, reservados y caducidad; nunca inventar valores.
5. Capacidades premium concedidas, especialmente `full_tools` e IA.
6. Explicación breve de datos ciegos.
7. Una acción principal y una alternativa segura.
8. Mensaje de error y recuperación cuando falte una dependencia.

## Reglas de navegación

- `verified_base` permanece en la superficie principal.
- `premium_active` puede iniciar intercambio seguro hacia `pro`.
- `premium_payment_pending` no crea sesión premium.
- `staff_only` puede iniciar intercambio únicamente hacia `crm`.
- Un usuario profesional nunca recibe una CTA hacia CRM.
- La navegación conserva un `correlation_id` opaco, no datos personales.

## Accesibilidad y claridad

- El estado debe ser texto, no solo color o icono.
- Los bloqueos deben explicar el siguiente paso.
- Las CTAs deben ser accionables y únicas por prioridad.
- Los errores deben permitir reintento sin duplicar efectos.
