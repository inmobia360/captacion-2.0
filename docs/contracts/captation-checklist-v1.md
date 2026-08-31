# Checklist de entrevista y publicación v1

## Bloques de diagnóstico

### 1. Propiedad y representación

- titular o titulares identificados;
- capacidad para vender documentada;
- consentimiento de todos los titulares cuando aplique;
- profesional que representa al cliente declarado.

### 2. Motivación y plazo

- motivo principal;
- urgencia;
- fecha objetivo;
- dependencia de compra, traslado, herencia u otra operación.

### 3. Situación jurídica y ocupación

- hipoteca y cargas conocidas;
- arrendatarios u ocupantes;
- herencia o usufructo;
- VPO u otras limitaciones;
- necesidad de revisión jurídica.

### 4. Inmueble y documentación

- tipo, superficie y características;
- fotografías y descripción;
- certificado energético cuando proceda;
- nota simple u otra documentación requerida;
- datos sensibles separados de la ficha pública.

### 5. Mercado y estrategia

- precio solicitado;
- comparables disponibles;
- rango orientativo;
- demanda compatible;
- estrategia rápida, mercado o aspiracional;
- advertencias y supuestos.

## Estados de completitud

| Estado | Significado | Publicación |
|---|---|---|
| `empty` | no iniciado | no |
| `draft` | información parcial guardada | no |
| `needs_information` | falta dato crítico | no |
| `needs_expert` | requiere revisión profesional | solo si la regla lo permite, con advertencia |
| `ready_for_publication` | mínimos completos y advertencias revisadas | sí |
| `published` | ficha publicada con datos ciegos | sí |

## Mínimos para publicar

- tipo de operación;
- ubicación general;
- precio solicitado;
- características esenciales;
- representación y titularidad declaradas;
- consentimiento requerido;
- advertencias de documentación faltante;
- separación entre datos públicos y restringidos.

La ausencia de un dato no debe ocultarse: debe expresarse como `Pendiente de
verificación` o `No aportado por el usuario`.

## Resultado para el agente

El sistema debe mostrar: porcentaje de completitud, bloqueos, riesgos, siguiente
acción y si el resultado puede publicarse, necesita información o requiere experto.
