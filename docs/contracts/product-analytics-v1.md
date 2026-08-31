# Contrato de analítica de producto v1

## Objetivo

Medir el recorrido completo entre los tres dominios para mejorar activación,
valor inicial, colaboración y conversión premium sin convertir la analítica en
una copia de datos personales.

## Eventos del recorrido

| Evento | Superficie | Momento |
|---|---|---|
| `landing_viewed` | pública | visita inicial |
| `value_cta_clicked` | pública | se elige empezar o explorar |
| `registration_started` | pública | comienza el alta |
| `registration_completed` | pública | alta aceptada |
| `onboarding_started` | pro/base | primera entrada autenticada |
| `first_action_selected` | pro/base | publicar o buscar |
| `record_published` | pro/base | captación o demanda publicada |
| `match_viewed` | pro/base | coincidencia consultada |
| `premium_gate_seen` | pro | función bloqueada por plan |
| `premium_session_started` | pro | entitlement avanzado validado |
| `tool_started` | pro | inicia `full_tools` o IA |
| `tool_completed` | pro | herramienta finalizada |
| `reservation_started` | pro | se muestra coste y condiciones |
| `reservation_confirmed` | pro | reserva creada |
| `collaboration_accepted` | pro | colaboración aceptada |
| `contract_signed` | pro | firma válida registrada |
| `operation_opened` | pro/CRM | operación pasa a estado abierto |
| `operation_closed` | pro/CRM | operación cerrada |
| `support_requested` | pro/CRM | se solicita ayuda |
| `access_denied` | cualquier | autorización rechazada |

## Propiedades permitidas

Cada evento puede incluir únicamente:

```json
{
  "event_name": "match_viewed",
  "occurred_at": "ISO-8601",
  "surface": "public|pro|crm",
  "actor_class": "visitor|base|premium|staff",
  "plan": "none|base|advanced|internal",
  "feature": "neutral-feature-name",
  "outcome": "success|blocked|error",
  "correlation_id": "opaque-id"
}
```

No se deben enviar email, teléfono, dirección, nombre, IP completa, contraseña,
token, texto libre de usuario ni contenido de propietario.

## Métricas prioritarias

- tiempo desde registro hasta primera acción útil;
- porcentaje que completa onboarding;
- publicaciones y búsquedas iniciadas/completadas;
- matches vistos y reservas confirmadas;
- reservas expiradas o rechazadas;
- activaciones de `pro` con pago activo;
- uso y finalización de `full_tools` e IA;
- operaciones abiertas y cerradas;
- abandono por etapa y motivo;
- accesos denegados por superficie y causa categorizada.

## Privacidad y calidad

- Retención limitada y documentada por evento.
- Identificadores opacos y rotables.
- Consentimiento cuando la analítica no sea estrictamente necesaria.
- Separar métricas observadas de proyecciones comerciales.
- No usar cifras de producción como datos de prueba.

## Pendientes

- Elegir almacenamiento y proveedor de analítica.
- Definir consentimiento y retención legal.
- Confirmar si existen ya eventos en producción.
- Crear dashboard de embudo después de validar el contrato.
