<?php
// Conecta con la base de datos para validar credenciales del usuario o administrador.
include "db.php";

// Inicia la sesión para guardar sesión del usuario.
session_start();

// Login script begins here.
// Si las credenciales ingresadas coinciden con los datos de la base, se devuelve "login_success".
// Ese valor es leído por la función AJAX que llamó este script.

// Verifica si llegaron los datos de email y password por POST.
if(isset($_POST["email"]) && isset($_POST["password"])){
	// Escapa caracteres especiales para evitar inyección SQL simple.
	$email = mysqli_real_escape_string($con,$_POST["email"]);
	$password = $_POST["password"];

	// Busca si el email y la contraseña coinciden en la tabla de usuarios normales.
	$sql = "SELECT * FROM user_info WHERE email = '$email' AND password = '$password'";
	$run_query = mysqli_query($con,$sql);
	$count = mysqli_num_rows($run_query);
    $row = mysqli_fetch_array($run_query);

    // Si el usuario viene desde el carrito, existe una cookie con productos guardados.
    // Se aprovecha esa cookie para migrar ese carrito al usuario recién logueado.
	if($count == 1){
			if (isset($_COOKIE["product_list"])) {
				$p_list = stripcslashes($_COOKIE["product_list"]);
				// Decodifica la cookie JSON guardada con los IDs de productos del carrito.
				$product_list = json_decode($p_list,true);
				for ($i=0; $i < count($product_list); $i++) { 
						// Verifica si ese producto ya está asociado a este usuario.
						$verify_cart = "SELECT id FROM cart WHERE user_id = $_SESSION[uid] AND p_id = ".$product_list[$i];
						$result  = mysqli_query($con,$verify_cart);
						if(mysqli_num_rows($result) < 1){
							// Si es la primera vez que se agrega ese producto, se actualiza el usuario del carrito.
							$update_cart = "UPDATE cart SET user_id = '$_SESSION[uid]' WHERE ip_add = '$ip_add' AND user_id = -1";
							mysqli_query($con,$update_cart);
						}else{
							// Si ya existe, elimina la copia antigua del carrito anónimo.
							$delete_existing_product = "DELETE FROM cart WHERE user_id = -1 AND ip_add = '$ip_add' AND p_id = ".$product_list[$i];
							mysqli_query($con,$delete_existing_product);
						}
				}
				// Borra la cookie para evitar duplicados.
				setcookie("product_list","",strtotime("-1 day"),"/");
				// Envía un retorno especial para indicar que el usuario viene desde el carrito.
				echo "cart_login";
				exit();
			}

            // Si el login es normal, se guardan los datos en la sesión.
			$_SESSION["uid"] = $row["user_id"];
			$_SESSION["name"] = $row["first_name"];
			$ip_add = getenv("REMOTE_ADDR");

            // Actualiza los registros del carrito y wishlist para asociarlos al usuario autenticado.
			$sql = "UPDATE cart SET user_id = '$_SESSION[uid]' WHERE ip_add='$ip_add' AND user_id = -1";
			$wishlist_sql = "UPDATE wishlist SET user_id = '$_SESSION[uid]' WHERE ip_add='$ip_add' AND user_id = -1";
			if(mysqli_query($con,$sql)){
				echo "login_success";
				$BackToMyPage = $_SERVER['HTTP_REFERER'];
				if(mysqli_query($con,$wishlist_sql)){
						if(!isset($BackToMyPage)) {
							header('Location: '.$BackToMyPage);
							echo"<script type='text/javascript'>
						
						</script>";
						} else {
							echo "<script> location.href='index.php'; </script>" ;// Página por defecto.
						} 
				}
			}

            exit;

		}else{
                // Si no encontró al usuario normal, intenta iniciar sesión como administrador.
                $email = mysqli_real_escape_string($con,$_POST["email"]);
                $password =md5($_POST["password"]) ;
                $sql = "SELECT * FROM admin_info WHERE admin_email = '$email' AND admin_password = '$password'";
                $run_query = mysqli_query($con,$sql);
                $count = mysqli_num_rows($run_query);

            // Si el administrador existe en la base, se crea su sesión.
            if($count == 1){
                $row = mysqli_fetch_array($run_query);
                $_SESSION["uid"] = $row["admin_id"];
                $_SESSION["name"] = $row["admin_name"];
                $ip_add = getenv("REMOTE_ADDR");

                // Si el login fue exitoso, responde con login_success y redirige al panel del admin.
                echo "login_success";
                echo "<script> location.href='admin/add_products.php'; </script>";
                exit;

            }else{
                // Si no existe ni usuario ni administrador, se informa al usuario que debe registrarse.
                echo "<span style='color:red;'>Please register before login..!</span>";
                exit();
            }
	}
}
?>