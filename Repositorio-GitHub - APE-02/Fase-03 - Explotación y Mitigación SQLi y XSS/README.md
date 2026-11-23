
# Fase 3 - Ataques de inyección: SQLi y XSS

## Objetivo

Documentar el ciclo completo **Hallazgo → Mitigación → Verificación** para las vulnerabilidades de inyección SQL (SQLi) y Cross-Site Scripting (XSS) detectadas en la aplicación.

Las pruebas se realizaron con datos ficticios en un entorno local controlado y, cuando fue necesario, en DVWA u otro laboratorio autorizado.

---

## 1. Explotación Controlada - El "Antes"

### 1.1 SQL Injection (SQLi)

**Superficies analizadas**

- Inicio de sesión de usuario y administrador.
- Campo de correo electrónico en el registro.
- Consulta del historial de pedidos.
- Parámetro `p` de la vista de producto.

**Payloads probados**

```text
'
```

```text
' OR '1'='1' --
```

**Ejemplo utilizado en el formulario de inicio de sesión**

- Campo `email`: `' OR '1'='1' --`
- Campo `password`: cualquier contraseña

**Resultado esperado antes de la mitigación**

- La comilla simple puede alterar la sintaxis del SQL.
- La tautología puede modificar la condición de autenticación.
- La aplicación podría aceptar una condición verdadera y permitir un acceso no autorizado.

**Evidencia antes de la mitigación**

Coloque aquí las capturas:

- `![Prueba de comilla simple antes](capturas/sqli-comilla-antes.png)`
- `![Prueba de tautología antes](capturas/sqli-tautologia-antes.png)`
- `![Resultado en DVWA o en la aplicación antes](capturas/sqli-dvwa-antes.png)`

**Hallazgo observado**

La consulta original podía utilizar directamente el correo recibido mediante concatenación. Esto permitía que caracteres especiales modificaran la consulta SQL. También se identificó que el parámetro `p` de producto necesitaba validación estricta de entero.

---

### 1.2 XSS Reflejado

**Payload utilizado**

```html
<script>alert(1)</script>
```

La prueba se debe ingresar en un campo controlado que refleje o almacene y muestre contenido, por ejemplo una reseña o el parámetro de un formulario. En el proyecto se probaron además:

```html
<b>Prueba</b>
<img src=x onerror=alert(1)>
<svg onload=alert(1)>
```

**Resultado esperado antes de la mitigación**

- La etiqueta `<script>` puede ejecutarse si el navegador recibe el contenido como HTML.
- Una etiqueta que se refleje sin escape puede mostrar una ventana `alert(1)`.
- Los atributos `onerror` y `onload` pueden ejecutarse si se permiten imágenes o SVG sin codificación.

**Evidencia antes de la mitigación**

- `![XSS reflejado antes](capturas/xss-reflejado-antes.png)`
- `![Payload ejecutándose o reflejado antes](capturas/xss-script-antes.png)`

---

## 2. Mitigación en Código

### 2.1 Mitigación contra SQLi: consultas preparadas

En `login.php`, el correo y otros valores se enviaron como parámetros a una consulta preparada:

```php
$stmt = mysqli_prepare(
    $con,
    "SELECT * FROM user_info WHERE email = ? LIMIT 1"
);

if ($stmt === false) {
    throw new RuntimeException('No se pudo preparar la consulta.');
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$run_query = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_array($run_query);
```

En `myorders.php`, el identificador del usuario autenticado se vinculó como entero:

```php
$stmt_orders = mysqli_prepare(
    $con,
    "SELECT c.order_id, a.product_id, a.product_title,
            a.product_price, a.product_image, b.qty, b.amt, c.total_amt
       FROM products a, order_products b, orders_info c
      WHERE a.product_id=b.product_id
        AND c.user_id=?
        AND b.order_id=c.order_id
      ORDER BY c.order_id DESC"
);

mysqli_stmt_bind_param($stmt_orders, "i", $_SESSION["uid"]);
mysqli_stmt_execute($stmt_orders);
$query = mysqli_stmt_get_result($stmt_orders);
```

En `product.php`, el parámetro recibido por URL se valida antes de usarlo:

```php
$product_id = filter_var($_GET['p'], FILTER_VALIDATE_INT);

if ($product_id === false) {
    http_response_code(400);
    exit('Invalid product identifier.');
}

$stmt_product = mysqli_prepare(
    $con,
    "SELECT * FROM products AS P, categories AS C
      WHERE P.product_cat = C.cat_id
        AND P.product_id = ?"
);

mysqli_stmt_bind_param($stmt_product, "i", $product_id);
mysqli_stmt_execute($stmt_product);
```

Las consultas preparadas separan la estructura SQL de los datos. Por ello, los caracteres introducidos por el atacante no cambian la sintaxis de la consulta.

---

### 2.2 Mitigación contra XSS: escapado de salida

En `review_action.php`, el nombre y el comentario se escaparon antes de insertarlos en el HTML:

```php
$name_safe = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$review_safe = htmlspecialchars($review, ENT_QUOTES, 'UTF-8');

echo '
<li>
    <div class="review-heading">
        <h5 class="name">' . $name_safe . '</h5>
    </div>
    <div class="review-body">
        <p>' . $review_safe . '</p>
    </div>
</li>';
```

En `product.php` y `myorders.php`, los datos recuperados de la base se escaparon antes de mostrarlos en contenido o atributos HTML:

```php
$product_title_safe = htmlspecialchars(
    $row['product_title'],
    ENT_QUOTES,
    'UTF-8'
);

$product_image_safe = htmlspecialchars(
    $row['product_image'],
    ENT_QUOTES,
    'UTF-8'
);
```

`ENT_QUOTES` convierte también las comillas, evitando que los datos cierren atributos HTML. `UTF-8` define explícitamente la codificación utilizada.

**Medida complementaria recomendada**

Como defensa adicional, se puede configurar una Política de Seguridad de Contenido (CSP) en `header.php` o mediante el servidor web. La directiva debe ajustarse a los recursos externos reales del proyecto:

```php
header(
    "Content-Security-Policy: default-src 'self'; "
    . "script-src 'self'; "
    . "style-src 'self'; "
    . "img-src 'self' data:;"
);
```

> Nota: verificar las librerías, fuentes, imágenes y scripts utilizados antes de activar la CSP, porque una política demasiado restrictiva puede bloquear recursos legítimos.

---

## 3. Verificación / Re-Test - El "Después"

### 3.1 Re-test de SQLi

Se repitieron exactamente las pruebas realizadas antes de la mitigación:

| Payload | Resultado esperado después |
|---|---|
| `'` | Rechazo o ausencia de coincidencias, sin alterar la consulta. |
| `' OR '1'='1' --` | No autenticación ni acceso, salvo credenciales válidas reales. |
| Texto o comillas en `p` | Respuesta HTTP `400` por identificador no válido. |

**Resultado de la verificación**

- El inicio de sesión rechaza entradas manipuladas y mantiene la autenticación mediante credenciales válidas.
- El registro mantiene sus validaciones y no permite una inserción manipulada.
- El historial consulta únicamente los pedidos asociados al `uid` de la sesión autenticada.
- El parámetro `p` rechaza valores no numéricos con HTTP `400`.

**Capturas después de la mitigación**

- `![Comilla simple bloqueada](capturas/sqli-comilla-despues.png)`
- `![Tautología bloqueada](capturas/sqli-tautologia-despues.png)`
- `![Parámetro inválido rechazado](capturas/sqli-parametro-400-despues.png)`

---

### 3.2 Re-test de XSS

Se repitieron las pruebas reflejadas y almacenadas con el mismo payload:

```html
<script>alert(1)</script>
```

**Resultado de la verificación**

- Las etiquetas se muestran como texto y no como elementos HTML ejecutables.
- No aparece la ventana `alert(1)`.
- Los atributos `onerror` y `onload` no se ejecutan al mostrar las reseñas.
- Los títulos e imágenes recuperados de productos se presentan con escape seguro.

**Capturas después de la mitigación**

- `![Script mostrado como texto](capturas/xss-reflejado-despues.png)`
- `![Sin ventana alert](capturas/xss-sin-alert-despues.png)`
- `![Payload almacenado neutralizado](capturas/xss-almacenado-despues.png)`

---

## Matriz Hallazgo - Mitigación - Verificación

| ID | Hallazgo | Ubicación | Mitigación | Verificación |
|---|---|---|---|---|
| H-01 | El correo podía alterar la consulta SQL de autenticación. | `login.php` | `mysqli_prepare()` y `mysqli_stmt_bind_param()` | Comilla y tautología no inician sesión. |
| H-02 | Los datos de registro podían afectar un INSERT o la consulta de existencia. | `register.php` | Consultas preparadas independientes y validación. | Datos manipulados no permiten registros no autorizados. |
| H-03 | El historial podía depender de un identificador concatenado. | `myorders.php` | Consulta preparada con `uid` como entero. | Cada usuario visualiza únicamente sus propios pedidos. |
| H-04 | `p` aceptaba valores distintos de enteros. | `product.php` | `FILTER_VALIDATE_INT`, HTTP `400` y consulta preparada. | Texto, comillas y payloads son rechazados. |
| H-05 | Nombre y comentario podían interpretarse como HTML o JavaScript. | `review_action.php` | `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` al mostrar. | `<script>alert(1)</script>` y otros payloads se muestran como texto. |
| H-06 | Títulos e imágenes podían mostrarse sin escape en HTML o atributos. | `product.php` y `myorders.php` | Escapado de títulos e imágenes con `htmlspecialchars()`. | Los datos HTML de prueba no generan elementos ni scripts. |

---

## Pregunta de Control Nro. 3

**Las consultas preparadas mitigan la inyección SQL y el escapado de salida mitiga el XSS. ¿Por qué la validación de entrada por sí sola no basta para prevenir ambos tipos de ataque? Explique su respuesta con un ejemplo aplicado a su propio proyecto.**

La validación de entrada por sí sola no basta porque solo verifica el formato, la longitud o el tipo esperado de un dato. No controla el contexto en el que ese dato será interpretado:

- En una base de datos, un dato validado como texto puede contener caracteres con significado SQL si se concatena dentro de una consulta.
- En el navegador, un dato validado como texto puede contener etiquetas o eventos JavaScript si se escribe directamente en HTML sin codificar.

Por ejemplo, en este proyecto el campo de **comentario de una reseña** puede aceptar texto válido y, al mismo tiempo, contener el payload `<script>alert(1)</script>`. Aunque se valide como texto, debe enviarse mediante una consulta preparada para evitar que se modifique el SQL y debe escaparse con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` antes de mostrarse, para evitar que el navegador interprete el `<script>`.

De forma equivalente, el parámetro `p` de `product.php` se valida con `FILTER_VALIDATE_INT` y también se procesa mediante una consulta preparada. Esta combinación protege el contexto numérico y separa el identificador de la estructura SQL.
