# Contrato de entitlements v1 — planes y capacidades

## Objetivo

Representar de forma explícita qué puede utilizar cada usuario y evitar que el
acceso premium se deduzca únicamente de su rol o de la interfaz.

## Estados de acceso

| Estado | `pro` | `full_tools` | `ai_assistance` |
|---|---:|---:|---:|
| `none` | no | no | no |
| `base_active` | no | no | solo funciones IA base autorizadas |
| `advanced_active` | sí | sí | sí |
| `payment_pending` | no | no | no premium |
| `expired` | no | no | no premium |
| `suspended` | no | no | no |
| `staff_authorized` | no por plan | según permiso Staff | según permiso Staff |

## Respuesta canónica propuesta

```json
{
  "plan": {"id": "advanced", "status": "active", "payment_status": "active"},
  "capabilities": {"pro": true, "full_tools": true, "ai_assistance": true},
  "source": "server_authoritative"
}
```

## Reglas

- La API resuelve capacidades server-side en cada sesión y acción sensible.
- El frontend puede mostrar CTAs, pero nunca conceder capacidades.
- `pro` exige plan `advanced` y pago activo.
- Un cambio de pago puede revocar capacidades sin cambiar el frontend.
- Staff usa permisos internos independientes del plan comercial.
- Una capacidad desconocida se trata como denegada.

Durante la transición se pueden leer `role` y `plan_type` como fallback temporal,
pero el resultado debe normalizarse a este contrato.

## Pruebas mínimas

- base sin acceso a `pro`;
- advanced activo con las dos capacidades;
- pago pendiente sin capacidades premium;
- plan expirado revocado;
- Staff autorizado sin plan comercial;
- capability desconocida denegada;
- revocación durante sesión activa.
