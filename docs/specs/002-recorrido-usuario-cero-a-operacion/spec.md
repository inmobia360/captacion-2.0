# Spec 002 — Recorrido de usuario: de cero a operación colaborativa

## Contexto y objetivo

El usuario llega con dos problemas principales: tiene una captación sin comprador
o un comprador solvente sin inmueble. El recorrido debe llevarle desde la
comprensión del valor hasta una colaboración trazable, reduciendo incertidumbre,
fricción de registro, riesgo de puenteo, consumo opaco de créditos y abandono.

## Recorrido principal

```text
Descubrir valor
  → entender datos ciegos y 50/50
  → registrarse sin fricción
  → activar perfil y créditos
  → elegir objetivo: publicar o buscar
  → encontrar un match explicable
  → reservar sin revelar datos sensibles
  → aceptar colaboración
  → firmar ambos
  → desbloquear con crédito idempotente
  → gestionar operación
  → cerrar, medir y volver a colaborar
```

## Actores

- Visitante.
- Profesional con captación.
- Profesional con demanda/comprador.
- Colaborador aceptado.
- Sistema de pagos y créditos.
- Staff de operaciones.

## Historias de usuario

- H1: Como visitante quiero entender en pocos segundos qué problema resuelve la plataforma.
- H2: Como profesional quiero registrarme y saber qué puedo hacer inmediatamente.
- H3: Como captador quiero publicar una oportunidad protegiendo dirección y propietario.
- H4: Como colaborador quiero buscar una captación compatible sin gastar crédito por error.
- H5: Como ambas partes quiero aceptar y firmar las mismas condiciones antes de revelar información.
- H6: Como profesional quiero conocer el estado, coste y siguiente paso de cada colaboración.

## Requisitos funcionales (EARS)

- RF-1: CUANDO un visitante llegue a la web pública, EL SISTEMA debe explicar el problema, el beneficio, el 50/50, los datos ciegos y el siguiente paso sin exigir registro previo.
- RF-2: CUANDO un visitante seleccione empezar, EL SISTEMA debe mostrar claramente qué incluye el alta, la duración de los créditos y qué no se revela todavía.
- RF-3: CUANDO un usuario complete el registro, EL SISTEMA debe crear su perfil con estado de verificación explícito y mostrar un onboarding orientado a una primera acción.
- RF-4: CUANDO el usuario entre por primera vez, EL SISTEMA debe ofrecer dos caminos principales: publicar captación o buscar comprador/inmueble.
- RF-5: CUANDO se publique una captación, EL SISTEMA debe validar campos mínimos, ocultar datos sensibles y mostrar una previsualización antes de publicar.
- RF-6: CUANDO el usuario busque oportunidades, EL SISTEMA debe mostrar coincidencias explicables por zona, precio, características y demanda, diferenciando datos observados de estimaciones.
- RF-7: CUANDO el usuario intente reservar una oportunidad, EL SISTEMA debe mostrar coste, duración de la reserva, condiciones y resultado esperado antes de confirmar.
- RF-8: CUANDO exista una reserva, EL SISTEMA debe mantener ocultos teléfono, email, dirección exacta y datos registrales hasta que se cumplan las autorizaciones definidas.
- RF-9: CUANDO ambas partes acepten la colaboración, EL SISTEMA debe presentar la misma versión documental y registrar la aceptación individual de cada parte.
- RF-10: CUANDO ambas partes hayan firmado, EL SISTEMA debe habilitar únicamente el acceso autorizado y registrar el consumo de crédito con idempotencia.
- RF-11: SI la reserva expira, se rechaza o se cancela, EL SISTEMA debe liberar o revertir el crédito según la regla aprobada y explicar el estado al usuario.
- RF-12: MIENTRAS una operación esté abierta, EL SISTEMA debe mostrar estado, responsables, siguiente acción, documentos y métricas contractuales sin sustituirlas por estimaciones regionales.
- RF-13: CUANDO una operación se cierre, EL SISTEMA debe conservar el valor contractual, reparto y trazabilidad para métricas y auditoría.
- RF-14: SI Vera, un mapa o una fuente externa no está disponible, EL SISTEMA debe ofrecer una alternativa comprensible sin bloquear el recorrido principal.
- RF-15: CUANDO el usuario abandone un paso crítico, EL SISTEMA debe poder recuperar el contexto sin duplicar registros, cargos, reservas o solicitudes.

## Puntos de dolor cubiertos

| Dolor | Respuesta del recorrido |
|---|---|
| Captación sin comprador | CTA directa a publicar y matching nacional |
| Comprador sin inmueble | Búsqueda por demanda, zona y características |
| Miedo al puenteo | Datos ciegos, reserva, NDA y doble firma |
| Incertidumbre del coste | Precio y efecto del crédito antes de confirmar |
| Registro sin valor inmediato | Onboarding con primera acción y 3 créditos temporales |
| Desconfianza en cifras | Fuente, fecha, etiqueta de estimación y datos contractuales separados |
| Complejidad operativa | Estado, siguiente acción y documentos en una línea temporal |
| IA o mapas indisponibles | Fallback funcional y mensajes de recuperación |
| Cuentas fantasma / referidos abusivos | Hitos verificados y recompensas ligadas a valor real |

## Casos límite

- Registro incompleto o profesional pendiente de verificación.
- Captación sin porcentaje contractual.
- Coincidencia débil o con datos insuficientes.
- Crédito insuficiente, caducado, reservado o ya consumido.
- Doble clic o reintento de pago/desbloqueo.
- Una parte firma y la otra no.
- Datos XML duplicados o corruptos.
- Operación disputada o cancelada.
- Usuario que cambia de dominio durante una reserva activa.

## Fuera de alcance

- Rediseñar ahora las tres interfaces.
- Cambiar reglas económicas sin aprobación.
- Exponer datos privados para reducir fricción.
- Presentar estimaciones regionales como honorarios pactados.

## Criterios de finalización

- El recorrido está probado desde visitante hasta operación abierta.
- Cada paso tiene CTA, estado, error y siguiente acción.
- Cada RF tiene prueba asociada en las tres superficies afectadas.
- Costes, permisos, privacidad y estados financieros son visibles antes de acciones sensibles.
- Se puede recuperar un abandono sin duplicar efectos.
