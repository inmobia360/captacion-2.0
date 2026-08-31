# Spec 004 — Diagnóstico profesional de captación

## Contexto y objetivo

Antes de publicar una captación o recomendar una estrategia, el profesional
necesita ordenar motivación, plazo, titularidad, situación jurídica, documentación
y mercado. El sistema debe ayudar a diagnosticar sin convertir una puntuación en
una garantía de venta o asesoramiento jurídico.

## Actores

- Profesional captador.
- Propietario o representante autorizado.
- Colaborador autorizado.
- Vera como asistente informativa.
- Experto externo recomendado cuando proceda.

## Requisitos funcionales (EARS)

- RF-1: CUANDO el profesional inicie un diagnóstico, EL SISTEMA debe solicitar motivación, plazo, titularidad, número de propietarios y capacidad para vender.
- RF-2: CUANDO se complete la situación del inmueble, EL SISTEMA debe registrar cargas, hipoteca, arrendamiento, ocupación, herencia, usufructo o VPO como datos declarados por el usuario.
- RF-3: SI faltan datos críticos, EL SISTEMA debe mostrar el bloqueo, explicar su impacto y permitir guardar como borrador.
- RF-4: CUANDO existan datos de mercado suficientes, EL SISTEMA debe mostrar escenarios de estrategia y diferenciarlos de una tasación oficial.
- RF-5: SI el precio propuesto está fuera del rango de evidencia disponible, EL SISTEMA debe mostrar una advertencia y no impedir guardar el diagnóstico.
- RF-6: CUANDO el usuario solicite un score, EL SISTEMA debe mostrar factores, fuente, fecha, incertidumbre y no presentarlo como probabilidad garantizada.
- RF-7: SI la situación requiere criterio jurídico, fiscal, técnico o de compliance, EL SISTEMA debe recomendar escalar a un profesional competente.
- RF-8: CUANDO el diagnóstico se comparta, EL SISTEMA debe aplicar visibilidad por capas y registrar quién lo consultó.
- RF-9: EL SISTEMA debe permitir convertir un diagnóstico validado en checklist de publicación sin copiar datos sensibles innecesarios.

## Fuera de alcance

- Tasación oficial.
- Decidir automáticamente el precio final.
- Sustituir abogado, notario, asesor fiscal, arquitecto o compliance officer.
- Bloquear una captación solo por una puntuación baja.

## Casos límite

- Varios titulares con consentimiento incompleto.
- Herencia pendiente.
- Cargas desconocidas.
- Datos contradictorios del propietario y del profesional.
- Sin comparables suficientes.
- Precio aspiracional sin demanda observable.
- Operación de alquiler con reglas distintas de compraventa.
- Comunidad autónoma con requisitos específicos.

## Criterios de finalización

- El diagnóstico puede guardarse como borrador.
- Cada dato tiene origen y sensibilidad.
- Los escenarios separan precio anunciado, estimación y cierre.
- Las advertencias no se presentan como decisiones legales.
- El resultado produce una checklist de publicación trazable.
