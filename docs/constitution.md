# Constitución SDD — Compra Captación

Esta constitución define las reglas que deben cumplir las especificaciones, planes,
tareas, implementaciones y validaciones del proyecto.

## Principios innegociables

1. **La especificación manda**: ningún comportamiento nuevo se implementa sin una spec aprobada.
2. **Producción se protege**: la release `v1.5.33-stable-production` (`79a984d`) es el punto de restauración conocido hasta verificar otra referencia.
3. **Una fuente de verdad**: `CompraCaptacion/` es la fuente candidata actual; las copias no se mezclan ni se modifican durante la transición.
4. **Seguridad en servidor**: autenticación, autorización, roles, créditos y datos ciegos se validan en backend.
5. **Dinero y créditos son transaccionales**: todo cambio financiero debe ser atómico, idempotente y trazable en el ledger.
6. **Privacidad por defecto**: las direcciones exactas, datos registrales y PII solo se muestran bajo una autorización válida.
7. **Tests como puerta**: cada requisito funcional debe tener una prueba; no se avanza con pruebas fallidas sin una decisión documentada.
8. **Cambios pequeños**: cada tarea modifica un alcance acotado, declara sus RF y termina con una verificación reproducible.
9. **Documentación trazable**: todo cambio sustancial actualiza su spec, decisión técnica o estado actual correspondiente.
10. **Despliegue autorizado**: ningún agente ejecuta `git push` ni despliega a producción sin autorización explícita del Director del Proyecto.

## Convenciones SDD

- Las funcionalidades se numeran como `specs/NNN-nombre/`.
- Los requisitos funcionales usan identificadores `RF-N` y redacción EARS.
- Cada spec contiene contexto, actores, historias, RF, requisitos no funcionales, casos límite, fuera de alcance, criterios de finalización y dudas abiertas.
- Cada plan enlaza módulos y pruebas con los RF que cubre.
- Cada tarea incluye una línea verificable `Hecho cuando:`.

## Estado de adopción

- Fase actual: constitución y perímetro.
- Fuente candidata: `CompraCaptacion/`.
- Copias históricas y variantes: se conservan intactas hasta completar el inventario.
- Esta constitución no autoriza reorganizar carpetas, borrar archivos ni desplegar.
