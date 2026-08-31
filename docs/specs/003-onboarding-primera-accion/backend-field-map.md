# Mapa T1 — identidad, plan y créditos

## Fuentes actuales observadas

| Fuente | Campos observados | Uso previsto |
|---|---|---|
| `index.php` / `CAPTACION_CONFIG` | `loggedIn`, `emailVerified`, `currentUser`, `accessState`, `betaProgram`, endpoints | contexto inicial de la superficie principal |
| `api/auth.php?action=login` | `ok`, `displayName`, `email`, `phone`, `businessName`, `profileType`, `profileComplete`, `accessState`, `credits` | resultado de login y perfil inicial |
| `api/credits.php?action=status` | `wallet.available_balance`, `consumed_balance`, `pending_balance`, `plans`, `quick_recharges` | saldo y opciones de recarga |
| `api/credits.php?action=ledger` | `ledger` | historial de movimientos |

## Gaps detectados para el onboarding

- La respuesta `credits/status` no devuelve explícitamente `expires_at`, aunque
  la tabla `wallets` sí lo contiene y la spec exige mostrar caducidad.
- La respuesta de login calcula `plan_type` a partir del rol; eso no equivale
  todavía a un entitlement premium avanzado con `full_tools` e IA.
- No existe en el mapa actual un campo explícito y común de `capabilities`.
- `profileComplete` aparece como booleano, pero debe verificarse qué campos lo
  determinan y si coincide entre dominios.
- La existencia de `CAPTACION_CONFIG` en la aplicación principal no demuestra
  que `pro.compracaptacion.com` consuma el mismo contrato.

## Decisión recomendada

Crear un contrato de respuesta unificado antes de implementar la pantalla:

```json
{
  "identity": {"user_id": "opaque", "status": "verified"},
  "plan": {"id": "advanced", "status": "active", "payment_status": "active"},
  "capabilities": ["full_tools", "ai_assistance"],
  "wallet": {"available": 3, "expires_at": "ISO-8601"},
  "next_actions": ["publish_record", "search_opportunity"]
}
```

Este contrato es una propuesta; no debe añadirse al backend productivo sin una
spec y pruebas específicas.
