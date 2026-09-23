# Manual de Usuario

## Sistema de Pedidos de La Despensa

Este manual explica cómo utilizar la aplicación para administrar el catálogo, generar pedidos de compra desde Excel, consultar el historial, generar reportes y descargar documentos.

La guía describe el comportamiento de la versión actualmente implementada. Los nombres de las opciones se muestran en la interfaz web y pueden variar ligeramente si se personaliza el frontend.

## 1. Objetivo Del Sistema

La aplicación centraliza el proceso de compras de alimentos y productos de despensa:

- Mantiene categorías y productos con sus precios y presentaciones.
- Lee un archivo Excel con códigos y cantidades.
- Busca esos códigos en el catálogo.
- Agrupa los productos por categoría.
- Calcula subtotal, IVA y total.
- Permite revisar el pedido antes de guardarlo.
- Guarda el pedido y sus líneas para consulta posterior.
- Descarga cada pedido en XLSX o PDF.
- Consolida pedidos por periodo, sede, categoría o producto en reportes XLSX y PDF.

## 2. Acceso Y Requisitos

### Acceso local

En el entorno local la aplicación normalmente está disponible en:

```text
http://localhost:8080
```

La URL de producción depende de la instalación de la organización.

### Cuenta de usuario

Se puede ingresar con una cuenta existente o utilizar **Registrarse** si esa opción está habilitada. El registro solicita:

- Nombre.
- Correo electrónico único.
- Contraseña.
- Confirmación de contraseña.

Después de registrar la cuenta, el sistema inicia sesión y dirige al Dashboard.

### Inicio de sesión

1. Abra la pantalla **Iniciar sesión**.
2. Escriba el correo y la contraseña.
3. Active **Remember me** si desea conservar la sesión en ese navegador.
4. Seleccione **Log in**.

Después de autenticarse se muestra el Dashboard. Los formularios validan el correo y aplican limitación después de varios intentos fallidos.

### Recuperación de contraseña

1. En el inicio de sesión seleccione **Forgot your password?**.
2. Escriba el correo asociado a la cuenta.
3. Abra el enlace recibido y defina una nueva contraseña.

En la configuración incluida por defecto, el correo utiliza el controlador `log`. En ese caso el enlace se registra en los logs del servidor y no se envía a una bandeja de correo hasta que el administrador configure un proveedor SMTP u otro mailer.

## 3. Navegación Principal

Una vez autenticado, el menú lateral contiene:

| Opción | Función |
|---|---|
| Dashboard | Resumen de categorías, productos, pedidos y los cinco pedidos más recientes. |
| Categorías | Crear, editar, ordenar y marcar categorías con o sin IVA. |
| Productos | Consultar, buscar, crear, editar, eliminar e importar el catálogo. |
| Pedidos | Consultar el historial paginado y abrir el detalle de cada pedido. |
| Nuevo Pedido | Cargar un Excel y generar un nuevo pedido. |
| Reportes | Consolidar pedidos y descargar el resultado. |

En teléfonos el menú se abre con el botón de menú de la barra superior.

## 4. Perfiles Y Permisos

La aplicación no tiene un administrador de roles configurable desde la interfaz. La regla especial actual depende del ID exacto del usuario en la base de datos.

| Usuario | Dashboard | Nuevo pedido | Ver pedidos | Eliminar pedidos | Categorías | Productos | Reportes |
|---|---:|---:|---:|---:|---:|---:|---:|
| Usuario con ID distinto de 3 | Sí | Sí | Sí | Sí | Sí | Sí | Sí |
| Usuario con ID 3 | Sí | Sí | Sí | No | No | No | No |

El usuario con ID 3 no puede acceder a las rutas restringidas aunque intente abrirlas directamente. La validación se hace en el servidor, no solo ocultando opciones del menú.

## 5. Preparación Del Catálogo

Antes de generar pedidos debe existir un catálogo consistente. El orden recomendado es:

1. Crear o revisar las categorías.
2. Marcar correctamente cuáles aplican IVA.
3. Cargar o registrar los productos.
4. Revisar códigos y precios.

### 5.1 Categorías

Abra **Categorías** y seleccione **Nueva Categoría**.

Complete:

- **Nombre**: nombre visible del grupo, por ejemplo `CARNES Y ABARROTES`.
- **Orden**: número entero que define el orden de aparición en las vistas y exportaciones. Un número menor aparece primero.
- **Aplica IVA (19%)**: active la casilla cuando los productos de la categoría deben formar parte de la base gravable.

Seleccione **Guardar**. Para cambiar una categoría use **Editar** y para eliminarla use **Eliminar**, confirmando la acción.

El seeder incluido trae estas categorías iniciales:

| Orden | Categoría | IVA |
|---:|---|---:|
| 1 | SALSAS ALITAS | Sí |
| 2 | SALSAS VARIAS | Sí |
| 3 | ALIÑOS | Sí |
| 4 | POLLO | Sí |
| 5 | CARNES Y ABARROTES | Sí |
| 6 | CANASTILLAS | Sí |
| 7 | EMPAQUES Y MISCELANEA | Sí |
| 8 | FLETE CALI | Sí |
| 9 | PRODUCTOS SIN IVA | No |

### 5.2 Registrar Un Producto

Abra **Productos > Nuevo Producto** y complete:

- **Código**: código que llegará en el archivo de pedido. Es único.
- **Categoría**: grupo al que pertenece el producto.
- **Descripción**: nombre completo del producto.
- **Precio por Unidad**: precio de una unidad individual.
- **Precio por Presentación**: precio que se utilizará para calcular el pedido.
- **Presentación**: empaque o cantidad comercial, por ejemplo `Bolsa x 1 KG`.

Seleccione **Guardar**. Para corregir un producto use **Editar**.

Aunque la interfaz no marca todos los campos con el mismo indicador visual, el producto debe tener código, descripción, categoría y ambos precios. La presentación y el precio de presentación deben diligenciarse para que el cálculo sea confiable.

### 5.3 Buscar Productos

En **Productos** puede:

- Buscar por código o descripción.
- Filtrar por categoría.
- Combinar búsqueda y categoría.
- Navegar entre páginas de 15 productos.

El filtro se aplica automáticamente unos instantes después de escribir o cambiar la categoría.

### 5.4 Importar Productos

1. Abra **Productos**.
2. Seleccione **Importar Excel**.
3. Arrastre el archivo a la zona de carga o selecciónelo desde el equipo.
4. Seleccione **Importar**.
5. Revise el mensaje con el número de productos insertados y las filas con error.

El formato exacto está documentado en [Formato de archivos Excel](FORMATO-EXCEL.md). La importación inserta productos nuevos; no actualiza automáticamente los existentes. Si el archivo tiene un código ya registrado, corrija o elimine esa fila antes de repetir el proceso.

## 6. Generar Un Pedido

### 6.1 Datos necesarios

Antes de comenzar tenga a mano:

- Un archivo `.xlsx` o `.xls` con la columna de código y la cantidad.
- El número de remisión.
- La sede correspondiente.
- La fecha del pedido.

La estructura del Excel se explica en [Formato de archivos Excel](FORMATO-EXCEL.md).

### 6.2 Cargar El Archivo Y Los Datos Generales

1. Seleccione **Nuevo Pedido** en el menú o en el Dashboard.
2. Cargue el archivo Excel arrastrándolo a la zona de carga o seleccionándolo.
3. Compruebe que se muestre el nombre y tamaño del archivo.
4. Escriba la **Remisión**.
5. Seleccione la **Sede**.
6. Verifique la **Fecha**. Por defecto se propone la fecha del navegador.
7. Seleccione **Vista Previa**.

La remisión debe tener máximo 20 caracteres. El sistema no permite repetir la misma combinación de remisión y sede. La misma remisión sí puede utilizarse para otra sede.

### 6.3 Sedes Disponibles

Las sedes del selector son:

| Sede | Centro de operaciones |
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

Si el archivo contiene una columna `C.O.`, el sistema puede ajustar o filtrar la sede. La vista previa muestra un aviso cuando esto sucede. Lea el aviso antes de confirmar.

### 6.4 Revisar La Vista Previa

La vista previa muestra:

- Remisión.
- Sede final utilizada.
- Fecha.
- Centro de operaciones detectado, si existe.
- Productos agrupados por categoría.
- Código, descripción, precio unitario, presentación, precio de presentación, cantidad y total de cada línea.
- Subtotal de cada categoría.
- Subtotal general.
- IVA calculado.
- Total general.

#### Códigos no encontrados

Si aparece el aviso **Códigos no encontrados en el catálogo**, esos códigos no tienen un producto correspondiente y no se incluyen en el pedido ni en sus totales.

Antes de confirmar, busque el código en **Productos**. Si corresponde a un producto real, regístrelo o corrija el código del archivo. Si confirma sin corregirlo, el pedido se guardará sin esas líneas.

#### Agregar productos manualmente

La vista previa permite agregar productos que no venían en el archivo:

1. Active **Deseo agregar productos adicionales manualmente**.
2. Busque por código o descripción.
3. Seleccione el producto.
4. Escriba la cantidad.
5. Seleccione **Agregar**.
6. Repita el proceso si necesita más productos.
7. Use **Eliminar** junto a un producto agregado si debe retirarlo.

Los productos manuales se suman al subtotal, al IVA correspondiente y al total. La cantidad manual debe ser un entero mayor o igual a 1.

### 6.5 Confirmar El Pedido

1. Revise todos los grupos y los totales.
2. Desplácese hasta el bloque final de confirmación.
3. Seleccione nuevamente el archivo Excel original en **Selecciona nuevamente el archivo para confirmar**.
4. Seleccione **Confirmar Pedido**.

El segundo cargue del archivo es obligatorio porque el archivo usado para la vista previa no se conserva entre solicitudes. Si no se vuelve a seleccionar, el botón permanece deshabilitado o la validación solicita el archivo.

Al confirmar, el sistema vuelve a leer el archivo, aplica la misma resolución de sede y agrega los productos manuales enviados desde la vista previa. El pedido se guarda dentro de una transacción y luego se abre su detalle.

## 7. Consultar Pedidos

### 7.1 Historial

Abra **Pedidos** para ver:

- Sede.
- Remisión.
- Fecha.
- Usuario que lo realizó.
- Número de productos.
- Total.
- Acciones disponibles.

El historial se ordena por fecha descendente y luego por ID. Se muestran 15 pedidos por página.

### 7.2 Detalle

Seleccione **Ver** en un pedido. El detalle contiene la información general, las líneas agrupadas por categoría y los totales.

El sistema conserva en cada línea la cantidad y los precios usados al crear el pedido. Esto permite revisar qué se registró originalmente aunque el catálogo se actualice posteriormente.

### 7.3 Descargar Un Pedido

En el detalle seleccione:

- **Descargar XLSX** para obtener una hoja de cálculo con encabezado, datos de sede y remisión, grupos de productos, subtotales y totales.
- **Descargar PDF** para obtener un documento en formato carta horizontal con el logo, los datos del pedido, el detalle y los totales.

Los nombres de archivo siguen este patrón:

```text
PEDIDO_<remision>_<AAAAMMDD>.xlsx
PEDIDO_<remision>_<AAAAMMDD>.pdf
```

### 7.4 Eliminar Un Pedido

En el historial, seleccione **Eliminar** y confirme. La operación no se puede deshacer desde la interfaz.

Al eliminar un pedido también se eliminan sus líneas asociadas. El usuario con ID 3 no tiene esta opción.

## 8. Generar Reportes

Abra **Reportes**. Los reportes se calculan sobre las líneas de pedido guardadas y pueden filtrarse por:

- Fecha de inicio, obligatoria.
- Fecha final, obligatoria y mayor o igual a la fecha de inicio.
- Sede, opcional.
- Grupo o categoría, opcional.
- Uno o varios productos específicos, opcional.

### 8.1 Procedimiento

1. Seleccione **Fecha inicio**.
2. Seleccione **Fecha fin**.
3. Si es necesario, seleccione una sede.
4. Si es necesario, seleccione un grupo.
5. Para productos específicos, escriba parte del código o descripción.
6. Seleccione los productos sugeridos. Puede elegir varios.
7. Seleccione **Calcular**.
8. Revise el resumen y los dos detalles.

El rango de fechas incluye ambos extremos. Por ejemplo, del 1 al 30 de junio incluye pedidos del 1 y del 30.

### 8.2 Interpretar El Resultado

El resumen muestra:

- **Pedidos incluidos**: cantidad de pedidos que tienen líneas coincidentes.
- **Productos incluidos**: cantidad de códigos distintos coincidentes.
- **Líneas incluidas**: cantidad de líneas de pedido incluidas.
- **Cantidad**: suma de las cantidades.
- **Subtotal**: suma de los totales de línea antes del IVA.
- **IVA calculado**: IVA de las líneas cuyas categorías aplican IVA.
- **Valor total**: subtotal más IVA.

El bloque **Detalle por pedido** consolida el resultado por pedido. El bloque **Detalle por producto** consolida el resultado por código.

Cuando se selecciona una categoría y productos específicos, ambos filtros se aplican al mismo tiempo. Solo se muestran los productos seleccionados que pertenecen al grupo seleccionado.

### 8.3 Exportar Un Reporte

Después de calcular el reporte, seleccione:

- **Descargar XLSX** para obtener un libro con filtros, resumen, detalle por pedido y detalle por producto.
- **Descargar PDF** para obtener el mismo contenido en formato carta horizontal.

Los nombres de archivo siguen este patrón:

```text
REPORTE_PEDIDOS_<sede>_<fechaInicio>_<fechaFin>.xlsx
REPORTE_PEDIDOS_<sede>_<fechaInicio>_<fechaFin>.pdf
```

Cuando no se filtra una sede, el nombre utiliza `TODAS_SEDES`.

## 9. Perfil Y Sesión

Desde el icono de configuración junto al usuario se puede abrir **Profile** para:

- Cambiar nombre.
- Cambiar correo, que debe ser único.
- Cambiar contraseña.
- Eliminar la cuenta, confirmando la contraseña actual.

Para salir, seleccione el icono de cierre de sesión del menú lateral o de la barra móvil.

## 10. Errores Frecuentes

| Mensaje o situación | Causa probable | Acción recomendada |
|---|---|---|
| `El archivo es obligatorio` | No se seleccionó un archivo. | Seleccione un `.xlsx` o `.xls`. |
| `El archivo no tiene una extensión válida` | Se cargó CSV en un pedido o un formato no soportado. | Use `.xlsx` o `.xls`; CSV solo aplica a importación de productos. |
| Aparecen códigos no encontrados | El código del Excel no existe en el catálogo o perdió ceros iniciales. | Revise el código, el formato de la celda y el catálogo. |
| La vista previa queda sin productos | No se detectó un código válido, las cantidades son cero o ningún código existe. | Revise el encabezado `Item`, la columna de cantidad y los códigos. |
| `Ya existe una orden ... para la sede ...` | Se repitió la combinación remisión-sede. | Consulte el pedido existente o use otra remisión. |
| El sistema ajusta la sede | El Excel contiene un C.O. diferente al seleccionado. | Verifique el aviso y confirme que la sede final es correcta. |
| El archivo contiene varios C.O. y no hay filas para la sede | Se seleccionó una sede cuyo C.O. no aparece en el archivo. | Seleccione la sede correcta o corrija el archivo. |
| No se puede confirmar la vista previa | No se volvió a seleccionar el archivo. | Cargue nuevamente el Excel original en el bloque final. |
| El reporte exige fechas | Se intentó calcular o enviar filtros sin rango completo. | Diligencie ambas fechas y revise que la final no sea anterior. |
| No hay resultados en el reporte | No existen líneas para la combinación de filtros. | Amplíe el rango, quite filtros o revise la sede/categoría. |
| No aparece Categorías, Productos o Reportes | La cuenta tiene ID 3. | Solicite la operación a un usuario autorizado. |
| No se puede borrar una categoría o producto | Puede tener registros relacionados por claves foráneas. | Conserve la categoría/producto o solicite revisión técnica antes de eliminar. |

## 11. Buenas Prácticas Operativas

1. Mantenga actualizado el catálogo antes de cargar pedidos.
2. No cambie códigos de productos sin coordinar los archivos Excel que los utilizan.
3. Revise los precios antes de generar un pedido.
4. Confirme los códigos no encontrados antes de guardar.
5. Lea la sede final y el C.O. mostrados en la vista previa.
6. Guarde los XLSX o PDF de los pedidos que deban enviarse o archivarse.
7. Use remisiones consistentes y no reutilice una remisión para la misma sede.
8. Genere los reportes después de verificar que las categorías tienen la configuración fiscal correcta.
9. No ejecute repetidamente el seeder de categorías en una base de datos con información real, porque crea registros adicionales.
10. Reporte al administrador cualquier diferencia entre el total guardado del pedido y un reporte posterior.
