# Plan — Spec 005

## Orden de implementación

1. Contrato de enlace compartible y token opaco.
2. Endpoint de ficha pública ciega con controles de caducidad/revocación.
3. Botón contextual en captaciones y demandas.
4. CTA de compatibilidad y captura de intención.
5. Registro/login con retorno al recurso de origen.
6. Validación del invitado activo y recompensa bilateral idempotente.
7. Instrumentación del embudo y revisión antifraude.

## Decisiones de arquitectura

- Reutilizar las entidades canónicas de `records`, sin duplicar oportunidades.
- Separar el enlace público del identificador interno mediante token aleatorio.
- No usar el enlace como autorización para desbloquear datos.
- Mantener la colaboración y los créditos en endpoints transaccionales existentes o contratos nuevos explícitos.
- Construir primero el MVP en el proyecto operativo `CompraCaptacion/`; después portar a `pro`/CRM según permisos.

## Riesgos a validar

- Revocación y caducidad de enlaces.
- Scraping o enumeración de oportunidades.
- Abuso de autorreferidos y cuentas duplicadas.
- Diferencia entre clic útil y simple visita.
- Consentimiento para compartir información profesional.
