# Escenarios de precio y evidencia de mercado v1

## Separación obligatoria

| Concepto | Definición | Puede presentarse como garantía |
|---|---|---:|
| Precio solicitado | importe indicado por propietario o profesional | no |
| Precio anunciado | importe publicado | no |
| Valor orientativo | rango calculado con evidencia disponible | no |
| Tasación oficial | valoración emitida por técnico competente | solo por su emisor |
| Precio de cierre | importe contractual de operación cerrada | sí como dato histórico |

## Escenarios

| Escenario | Uso | Datos mínimos |
|---|---|---|
| `quick_sale` | priorizar velocidad | rango, demanda, comparables y supuestos |
| `market` | equilibrio entre precio y tiempo | rango central y evidencia |
| `aspirational` | probar precio superior | diferencia, demanda esperada y riesgo |

Cada escenario debe mostrar precio/rango, demanda observable, comparables, tiempo
de publicación observado, riesgo, fecha y fuente.

## Reglas de cálculo

- No usar multiplicadores visuales sin datos.
- Si no hay comparables suficientes, mostrar `Sin datos comparables`.
- La demanda y el tiempo observado no son predicciones de venta.
- Las operaciones cerradas conservan exclusivamente valores contractuales.
- El porcentaje regional solo es una estimación y nunca sustituye el pactado.
- La IA debe explicar supuestos e incertidumbre.

## Salida para el propietario

La interfaz debe explicar las consecuencias comerciales de cada escenario, no
ordenar un precio. Debe incluir advertencia de que el resultado es orientativo y
recomendar experto cuando corresponda.

## Fuentes permitidas

- datos propios autorizados;
- operaciones y captaciones del sistema;
- fuentes oficiales o licenciadas;
- fuentes públicas utilizadas conforme a sus términos y con fecha/atribución.
