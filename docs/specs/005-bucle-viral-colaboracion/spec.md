# Spec 005 — Bucle viral de colaboración profesional

## Objetivo

Convertir una demanda o captación real en una invitación útil: compartir una oportunidad ciega, atraer a otro profesional, llevarlo al match concreto y medir si la interacción termina en una oportunidad válida o colaboración.

## Alcance MVP

1. Enlace compartible para demandas y captaciones.
2. Ficha pública ciega con CTA contextual.
3. Lectura pública sin registro; registro profesional solo al iniciar colaboración.
4. Retorno al match que originó el registro.
5. Recompensa bilateral únicamente por invitado activo y validado.

## Flujo canónico

```text
Profesional publica demanda/captación
        ↓
Comparte enlace ciego
        ↓
Receptor consulta zona, tipo, precio/presupuesto y requisitos
        ↓
Pulsa “Tengo una propiedad compatible” / “Tengo un comprador compatible”
        ↓
Se registra o inicia sesión
        ↓
Vuelve al match concreto, no al dashboard genérico
        ↓
Completa perfil y envía propuesta de colaboración
        ↓
Se valida la oportunidad y se activa la recompensa bilateral
```

## Reglas de privacidad y acceso

- Nunca incluir dirección exacta, catastro, teléfono, email, nombres de clientes, documentos ni tokens en el enlace.
- El enlace identifica una oportunidad compartible mediante un token opaco, revocable y con caducidad.
- La ficha pública es de solo lectura y muestra datos ciegos mínimos.
- Registro no bloquea la lectura; sí bloquea iniciar colaboración, pedir acceso o ver datos sensibles.
- El CRM sigue siendo exclusivo de staff.
- `pro.compracaptacion.com` conserva sus reglas de plan avanzado, `full_tools` y asistencia IA.

## Recompensa bilateral

No se recompensa el registro vacío. El hito mínimo recomendado es: perfil profesional verificado + publicación válida o propuesta de colaboración aceptada. La recompensa debe ser idempotente, auditable, revocable ante fraude y definida en créditos/desbloqueos antes de activarse.

## No incluido en MVP

Rankings, insignias, embajadores territoriales, círculos privados, widgets, mapa agregado, tarjetas automáticas multicanal y casos de éxito. Quedan como extensiones posteriores.

## Hecho cuando

- Una oportunidad genera un enlace ciego revocable.
- La ficha pública no expone datos sensibles.
- El CTA conserva el contexto de la oportunidad durante registro/login.
- El profesional vuelve al match original.
- El incentivo no se duplica ante reintentos.
- El embudo compartidos → clics → registros → verificación → oportunidad → match → colaboración queda medible.
