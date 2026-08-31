# Definiciones de negocio del panel ejecutivo

Estado: aprobado por el propietario el 24/08/2026.

## Porcentaje de honorarios

El porcentaje regional es únicamente una **estimación**. No representa un honorario contractual confirmado.

Orden de prioridad:

1. Porcentaje pactado y guardado en la captación, demanda o acuerdo.
2. Porcentaje guardado en la operación.
3. Porcentaje regional estimativo.
4. Si no existe ninguna fuente, no calcular honorarios y mostrar `Pendiente de porcentaje`.

Toda cifra basada en el tercer nivel debe etiquetarse como `Estimación regional` y mostrar una advertencia de posible variación.

## Reparto

El reparto estándar de colaboraciones es 50/50, salvo que exista otro reparto guardado en el acuerdo u operación.

Las operaciones cerradas deben usar exclusivamente sus valores contractuales registrados. Nunca deben recalcularse con el porcentaje regional.

## Valor bruto, honorarios y pipeline

Son métricas distintas:

- **Valor bruto de cartera:** suma de precios de captaciones activas.
- **Presupuesto de demandas:** suma de presupuestos de demandas activas.
- **Honorarios totales estimados:** valor bruto aplicable multiplicado por el porcentaje de honorarios.
- **Pipeline potencial:** honorarios estimados de oportunidades con una coincidencia válida, multiplicados por la participación del agente.
- **Pipeline activo:** honorarios registrados o estimados de operaciones en `requested`, `agreed` o `in_progress`, excluyendo disputas y cancelaciones.
- **Honorarios cerrados:** valor contractual de operaciones en `closed`.

Las demandas sin coincidencia válida no se suman al pipeline potencial. Se muestran como presupuesto de demanda independiente.

## Estados de operaciones

Estados abiertos:

- `requested`
- `agreed`
- `in_progress`

Estado cerrado:

- `closed`

Estados excluidos del pipeline:

- `cancelled`
- `disputed`

Una operación en disputa se informa separadamente y no se presenta como negocio disponible ni como operación cerrada.

## Periodos y comparaciones

Los filtros disponibles serán `7 días`, `30 días`, `90 días`, `Año actual` y `Personalizado`.

Cada variación se compara con un periodo anterior de igual duración:

- 7 días frente a los 7 días inmediatamente anteriores.
- 30 días frente a los 30 días inmediatamente anteriores.
- 90 días frente a los 90 días inmediatamente anteriores.
- Año actual frente al mismo tramo del año anterior.
- Personalizado frente a un periodo anterior con la misma duración.

No se permiten tendencias generadas por reglas visuales o multiplicadores arbitrarios. Si no existe base comparable, se mostrará `Sin datos comparables`.

## Requisitos de presentación

El panel debe separar visualmente y mediante etiquetas:

- cartera;
- presupuesto;
- honorarios totales;
- parte del agente;
- pipeline potencial;
- pipeline activo;
- operaciones cerradas.

Las estimaciones deben incluir siempre su fuente y fecha de actualización.
