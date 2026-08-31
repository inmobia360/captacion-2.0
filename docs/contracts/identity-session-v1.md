# Contrato de identidad y sesión v1 — borrador

## Objetivo

Permitir que las tres superficies funcionen como un único producto sin duplicar
contraseñas, usuarios ni permisos y sin otorgar privilegios por cambiar de dominio.

## Autoridad

La autoridad propuesta es el núcleo de Compra Captación. La web pública inicia el
registro y el acceso profesional; `pro` consume la identidad profesional; el CRM
mantiene una autorización Staff adicional y nunca hereda privilegios solo por
existir una sesión profesional.

## Modelo recomendado

1. El usuario se autentica contra el núcleo.
2. El núcleo emite una sesión segura para la superficie autorizada.
3. Para entrar en `pro` se usa un código temporal de un solo uso, con vida muy
   corta, audiencia explícita y consumo atómico.
4. El dominio destino canjea el código por su propia sesión HttpOnly.
5. `pro` solo acepta usuarios con un plan avanzado de pago activo y las
   capacidades (`full_tools`, asistencia IA) concedidas por el núcleo.
6. El CRM solo acepta audiencia `crm` y roles Staff autorizados; no es una
   superficie pública ni profesional.
7. El dominio destino vuelve a comprobar rol, estado de verificación, plan,
   estado de pago y permisos
   en cada petición sensible.

No se enviarán contraseñas, hashes, cookies de otro dominio ni datos sensibles en
la URL.

## Datos mínimos del intercambio

```json
{
  "exchange_id": "opaque-single-use-id",
  "subject": "stable-user-id",
  "audience": "professional|crm",
  "issued_at": "ISO-8601",
  "expires_at": "ISO-8601",
  "nonce": "single-use-value"
}
```

El intercambio no debe contener dirección exacta, datos de propietario, saldo
completo, contraseña ni token reutilizable.

## Reglas de seguridad

- TLS obligatorio en las tres superficies.
- Cookies `Secure`, `HttpOnly` y `SameSite` según el flujo validado.
- Protección CSRF para peticiones autenticadas por cookie.
- `aud` obligatorio para impedir reutilización entre `pro` y `crm`.
- Expiración corta y almacenamiento de nonce usado para impedir replay.
- Revocación centralizada por usuario y sesión.
- Errores uniformes sin revelar si existe un usuario o qué permiso concreto falta.
- Auditoría de login, canje, fallo de canje, logout, revocación y acceso denegado.

## Estados mínimos

`anonymous`, `registered_pending`, `verified_professional`, `premium_active`,
`staff_active`, `suspended`, `revoked`.

## Entitlements premium

El acceso a `pro` no se deduce de la existencia de una cuenta. El núcleo debe
resolver explícitamente, como mínimo:

```json
{
  "plan": "advanced",
  "payment_status": "active",
  "capabilities": ["full_tools", "ai_assistance"]
}
```

La respuesta no debe conceder capacidades que el plan no incluya y debe poder
revocarlas cuando cambie el estado de pago.

## Compatibilidad y migración

- Mantener el acceso actual hasta comprobar el contrato real en producción.
- Añadir primero endpoints de diagnóstico sin mutar sesiones.
- Probar canje en entorno no productivo.
- Activar por audiencia y permitir rollback independiente por dominio.

## Pendientes

- Confirmar la autoridad de autenticación actual.
- Confirmar si existe sesión compartida entre subdominios.
- Confirmar cookies, dominios, expiración y roles activos.
- Elegir formato final del código temporal y almacenamiento de nonce.
