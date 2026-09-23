# Formato de Archivos Excel

Este documento describe los dos formatos de archivo que acepta la aplicación:

1. El archivo de cantidades que se utiliza para generar un pedido.
2. El archivo de catálogo que se utiliza para importar productos.

Los formatos no son intercambiables. El primero contiene códigos y cantidades; el segundo contiene la información completa de los productos.

## 1. Archivo Para Generar Pedidos

### Reglas generales

- Extensiones aceptadas: `.xlsx` y `.xls`.
- Se procesa únicamente la hoja activa del libro.
- El sistema busca la fila de encabezados recorriendo la hoja de arriba hacia abajo.
- La fila de encabezados es la primera que contiene una columna reconocible como código de producto.
- Las filas anteriores a los encabezados se ignoran. Esto permite que el archivo tenga títulos, logotipos o información introductoria.
- Después de encontrar los encabezados, cada fila se interpreta como un producto.
- Las filas sin código válido se ignoran.
- Las filas cuya cantidad sea cero, negativa o no interpretable se ignoran.
- El producto debe existir en el catálogo para que entre en el pedido. Los códigos que no existan se muestran como advertencia en la vista previa y no se incluyen en los totales.

### Encabezados reconocidos

Los encabezados se normalizan antes de compararlos: se eliminan acentos y símbolos, se convierten a mayúsculas y se quitan los espacios. Por eso las variantes de la tabla son equivalentes.

| Dato | Encabezados reconocidos | Obligatorio | Uso |
|---|---|---:|---|
| Código del producto | `ITEM`, `CODIGO ITEM`, `COD ITEM` | Sí | Busca el registro por `codigo_item`. |
| Cantidad | Cualquier encabezado que contenga `CANT` | Recomendado | Cantidad a pedir. |
| Centro de operaciones | `CO`, `C.O.`, `CENTRO DE OPERACIONES`, `CENTRO OPERACION` | No | Resuelve o filtra la sede. |

El nombre de la columna de descripción, producto, precio u otras columnas no se utiliza para generar el pedido. Solo importan las columnas reconocidas.

Si no se encuentra una columna de cantidad, el sistema usa como alternativa la sexta columna de la hoja, es decir, la columna `F`. Esta compatibilidad existe para archivos antiguos; es preferible incluir siempre un encabezado de cantidad.

### Ejemplo mínimo

```text
Item    Producto              Cantidad
1001    Pollo deshuesado      5
1002    Salsa de la casa      2
```

La columna `Producto` es informativa y puede tener otro nombre. El código debe coincidir con un `codigo_item` del catálogo.

### Ejemplo con centro de operaciones

```text
C.O.    Item    Producto              Cantidad
002     1001    Pollo deshuesado      5
002     1002    Salsa de la casa      2
```

También puede existir información antes de la fila de encabezados:

```text
Pedido de compras - semana 32
Generado por el área de operaciones

C.O.    Item    Producto              Cantidad
002     1001    Pollo deshuesado      5
```

### Normalización de códigos

El lector admite códigos numéricos y algunos formatos habituales de Excel:

| Valor visible en Excel | Código buscado |
|---|---|
| `1001` | `1001` |
| `1001.0` o `1001,0` | `1001` |
| `1.001` o `1,001` | `1001` |
| `001` almacenado como texto | `001` |

Para códigos con ceros iniciales se recomienda que la celda esté almacenada como texto. Si Excel convierte el código a número, esos ceros ya no pueden recuperarse desde el archivo.

### Normalización de cantidades

- La coma decimal se convierte en punto.
- Se eliminan caracteres que no sean números o punto.
- Se admiten cantidades decimales en el archivo, aunque la pantalla de productos manuales solicita enteros.
- La cantidad debe ser mayor que cero.

Ejemplos: `5` se interpreta como `5`; `2,5` como `2.5`; `1.000 kg` puede terminar interpretándose como `1.000` según el contenido de la celda. Para evitar ambigüedades, use valores numéricos simples.

## 2. Archivo Para Importar Productos

La opción **Productos > Importar Excel** acepta `.xlsx`, `.xls` y `.csv`.

### Encabezados exactos

En este flujo los encabezados deben coincidir literalmente con los nombres de la tabla. No se aplica la normalización de encabezados del parser de pedidos.

| Encabezado exacto | Obligatorio | Tipo / límite | Descripción |
|---|---:|---|---|
| `codigo_item` | Sí | Texto, máximo 50 caracteres en validación | Código único del producto. |
| `descripcion` | Sí | Texto, máximo 255 caracteres | Nombre o descripción comercial. |
| `precio_unidad` | Sí | Numérico, mínimo 0 | Precio de una unidad. |
| `presentacion` | Operativamente sí | Texto, máximo 100 caracteres en validación | Forma de venta o empaque. |
| `precio_presentacion` | Operativamente sí | Numérico, mínimo 0 | Precio utilizado para calcular el pedido. |
| `categoria_id` | Sí | Entero existente | ID numérico de la categoría en la base de datos. |

Aunque algunas reglas de validación permiten omitir `presentacion` o `precio_presentacion`, la estructura de base de datos los define como campos no nulos y el cálculo del pedido utiliza `precio_presentacion`. Para evitar errores y productos imposibles de calcular, diligencie ambos campos siempre.

### Ejemplo CSV

```csv
codigo_item,descripcion,precio_unidad,presentacion,precio_presentacion,categoria_id
1001,Pollo deshuesado,18000,"Caja x 5 KG",90000,4
1002,Salsa de la casa,8500,"Frasco x 1 KG",8500,1
1003,Producto sin IVA,12000,"Bolsa x 1 KG",12000,9
```

El `categoria_id` no es el número de orden visual de la categoría. Es el ID de la fila en la tabla `categories`. Verifique el ID antes de importar.

### Comportamiento de la importación

- La primera fila se toma como encabezado.
- Cada fila válida se inserta como un nuevo producto.
- La importación no actualiza productos existentes ni funciona como un proceso de sincronización.
- `codigo_item` es único. Una fila repetida puede provocar un error de base de datos.
- Las filas inválidas se omiten y el mensaje final indica cuántos productos se importaron y cuántas filas tuvieron errores.
- La pantalla actual no muestra el detalle de los errores por fila; conserve el archivo original y valide las filas rechazadas si el conteo no coincide.
- La importación no crea categorías automáticamente. Las categorías deben existir antes.

## 3. Sedes Y Centro De Operaciones

Las sedes disponibles para crear pedidos son fijas en el código de la aplicación.

| Sede | C.O. |
|---|---|
| CIUDAD JARDIN | 001 |
| UNICENTRO | 002 |
| JARDIN PLAZA | 004 |
| PANCE | 008 |
| BOCHALEMA PLAZA | 011 |
| CHIPICHAPE | No aplica |
| FLORA | No aplica |
| GRANADA | No aplica |
| LIMONAR | No aplica |
| LLANOGRANDE | No aplica |
| SAN FERNANDO | No aplica |

Cuando la sede seleccionada utiliza C.O.:

- Si el archivo no contiene C.O., se conserva la sede seleccionada.
- Si contiene un único C.O., el sistema puede ajustar automáticamente la sede a la sede asociada a ese C.O.
- Si contiene varios C.O., se conservan únicamente las filas del C.O. de la sede seleccionada.
- Si no quedan filas para la sede seleccionada, se muestra un error y no se genera la vista previa.
- Un C.O. individual no configurado provoca un error de validación.

Cuando la sede seleccionada no utiliza C.O., la información de C.O. del archivo se ignora y se conserva la sede seleccionada.

## 4. Lista De Comprobación

Antes de cargar un archivo de pedido:

1. Confirme que la hoja activa es la hoja que contiene los datos.
2. Incluya una columna `Item` o equivalente.
3. Incluya una columna cuyo encabezado contenga `Cant`.
4. Verifique que los códigos coincidan con el catálogo.
5. Revise los ceros iniciales de los códigos.
6. Si el archivo contiene varias sedes, incluya la columna `C.O.`.
7. Elimine filas de totales, subtotales o encabezados repetidos dentro de los datos.
8. Revise la advertencia de códigos no encontrados en la vista previa.
