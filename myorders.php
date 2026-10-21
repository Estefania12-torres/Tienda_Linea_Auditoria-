<?php
// Se incluye la conexión a la base de datos para consultar los pedidos del usuario.
include "db.php";

// Se carga el encabezado común del sitio para mantener el mismo diseño visual.
include "header.php";

?>

<!-- Se agrega la hoja de estilos específica para la vista de pedidos. -->
<link href="css/myorders.css" rel="stylesheet"/> 

<section class="section main main-raised">
	<div class="container-fluid ">
		<div class="wrap cf">
            <!-- Título principal de la vista. -->
            <h1 class="projTitle">All Your Orders</h1>

            <!-- Encabezado de la sección con el botón para seguir comprando. -->
            <div class="heading cf">
                <h1>My Orders</h1>
                <h1 style="margin-left:55%">qty</h1>
                <a href="store.php" class="continue">Continue Shopping</a>
            </div>

            <!-- Contenedor donde se listan los productos comprados. -->
            <div class="cart">
                <ul class="cartWrap">
                <?php
                // Si el usuario ha iniciado sesión, se consultan sus órdenes.
                if (isset($_SESSION["uid"])) {
                    // Consulta SQL que une productos, detalles de la orden y la cabecera de la orden.
                    // Busca todas las compras hechas por el usuario actual, ordenadas por la más reciente primero.
                    $sql="SELECT c.order_id,a.product_id,a.product_title,a.product_price,a.product_image,b.qty,b.amt,c.total_amt FROM products a,order_products b,orders_info c WHERE a.product_id=b.product_id AND c.user_id='$_SESSION[uid]' AND b.order_id=c.order_id ORDER BY `c`.`order_id` DESC";
                    $query = mysqli_query($con,$sql);

                    // Se muestran los items sólo si existen registros para este usuario.
                    if (mysqli_num_rows($query) > 0) {
                        // Variables para controlar si el pedido actual es diferente al anterior.
                        $prev_old = 0;
                        $prev_total = 0;
                        $i = 1;
                        $numRows = mysqli_num_rows($query);

                        // Recorremos cada fila obtenida en la consulta.
                        while ($row=mysqli_fetch_array($query)) {
                            // Se extraen los datos del producto y del pedido actual.
                            $product_id = $row["product_id"];
                            $product_title = $row["product_title"];
                            $product_price = $row["product_price"];
                            $product_image = $row["product_image"];
                            $qty = $row["qty"];
                            $amt=$row["amt"];
                            $total_amt=$row["total_amt"];
                            $order_id=$row["order_id"];

                            // Si es el primer producto o pertenece al mismo pedido anterior,
                            // se imprime un bloque con la información del producto y el estado.
                            if ($prev_old==0 || $prev_old==$order_id){
                                $prev_old=$order_id;
                                $prev_total = $total_amt;
                                $i++;

                                // Se imprime la fila de la compra con imagen, nombre, cantidad y precio.
                                echo '<li class="items even">
                                    <tr>
                                    <div class="infoWrap"> 
                                        <div class="cartSection">
                                        <img src="product_images/'.$product_image.'" alt="'.$product_title.'" class="itemImg" />
                                        <p class="itemNumber">#'.$product_id.'</p>
                                        <h3>'.$product_title.'</h3>
                                        
                                        <p> '.$qty.' x &#x20B9; '.$product_price.'</p>
                                        
                                        <p class="stockStatus"> Delivered</p>
                                        </div>  
                                    
                                        <div class="prodTotal cartSection"><p>'.$qty.'</p></div>
                                        <td>
                                        <div class="prodTotal cartSection">
                                        <p>&#x20B9; '.$product_price.'</p>
                                        </div>
                                        </td>
                                        <div class="cartSection removeWrap">
                                            <a href="#" class="remove">x</a>
                                        </div>
                                    </div>
                                    </tr>
                            </li>';
                            }
                            // Si cambia el número de pedido, se cierra el bloque actual y se muestra
                            // el resumen del pedido anterior (subtotal, envío, impuestos y total).
                            else{
                                $prev_old=$order_id;
                                $i++;

                                // Se imprime el resumen del pedido anterior antes de iniciar el siguiente.
                                echo'
                            </ul>
                        </div>  
                        <div class="special"><div class="specialContent">
                                Thanks for Using our Platform
                        </div></div>
                        <div class="subtotal cf">
                            <ul>
                            <li class="totalRow"><span class="label">Subtotal</span><span class="value">&#x20B9; '.$prev_total.'</span></li>
                            
                                <li class="totalRow"><span class="label">Shipping</span><span class="value">&#x20B9; 0.00</span></li>
                            
                                    <li class="totalRow"><span class="label">Tax</span><span class="value">&#x20B9; 0.00</span></li>
                                    <li class="totalRow final"><span class="label">Total</span><span class="value">&#x20B9;'.$prev_total.'</span></li>
                            
                            </ul>
                        </div>
            
                        
                        <div class="cart">
                            <ul class="cartWrap">
                                <li class="items even">
                                <tr>
                                    <div class="infoWrap"> 
                                        <div class="cartSection">
                                        <img src="product_images/'.$product_image.'" alt="'.$product_title.'" class="itemImg" />
                                        <p class="itemNumber">#'.$product_id.'</p>
                                        <h3>'.$product_title.'</h3>
                                        
                                        <p> '.$qty.' x &#x20B9; '.$product_price.'</p>
                                        
                                        <p class="stockStatus out"> Shipping</p>
                                        </div>  
                                    
                                        <div class="prodTotal cartSection"><p>'.$qty.'</p></div>
                                        <td>
                                        <div class="prodTotal cartSection">
                                        <p>&#x20B9; '.$product_price.'</p>
                                        </div>
                                        </td>
                                        <div class="cartSection removeWrap">
                                            <a href="#" class="remove">x</a>
                                        </div>
                                    </div>
                                    </tr>
                                </li>
                                ';
                                $prev_total = $total_amt;
                            }

                            // Cuando se llega al último registro, se imprime el total final del pedido activo.
                            if($i==$numRows+1){
                                echo '
                                 
                                    <div class="special"><div class="specialContent">
                                            Thanks for Using our Platform
                                    </div></div>
                                    <div class="subtotal cf">
                                        <ul>
                                        <li class="totalRow"><span class="label">Subtotal</span><span class="value">&#x20B9; '.$prev_total.'</span></li>
                                        
                                            <li class="totalRow"><span class="label">Shipping</span><span class="value">&#x20B9; 0.00</span></li>
                                        
                                                <li class="totalRow"><span class="label">Tax</span><span class="value">&#x20B9; 0.00</span></li>
                                                <li class="totalRow final"><span class="label">Total</span><span class="value">&#x20B9;'.$prev_total.'</span></li>
                                        
                                        </ul>
                                    </div>
                                ';
                            }
                        }
                    } else {
                        // Si no hay pedidos para este usuario, no se imprime nada y queda la vista vacía.
                    }
                }
                ?>
                </ul>
            </div>

            <!-- Comentario de ejemplo que quedó como referencia del diseño original.
                 Se conserva porque forma parte del código base del proyecto. -->
            <!--<li class="items even">Item 2</li>-->
        </div>
    </div>
 </section>

<?php
// Se carga el pie de página para mantener la misma estructura del sitio.
include "footer.php";
?>