<?php
// Conecta con la base de datos para validar credenciales del usuario o administrador.
include "db.php";

// Inicia la sesión para guardar sesión del usuario.
require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/password_security.php';
start_secure_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    begin_login_attempt('storefront');
}

// Login script begins here.
// Si las credenciales ingresadas coinciden con los datos de la base, se devuelve "login_success".
// Ese valor es leído por la función AJAX que llamó este script.

// Verifica si llegaron los datos de email y password por POST.
if(isset($_POST["email"]) && isset($_POST["password"])){
	$email = $_POST["email"];
	$password = $_POST["password"];

	// Busca si el email y la contraseña coinciden en la tabla de usuarios normales.
	$stmt = mysqli_prepare($con, "SELECT * FROM user_info WHERE email = ? LIMIT 1");
	mysqli_stmt_bind_param($stmt, "s", $email);
	mysqli_stmt_execute($stmt);
	$run_query = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($run_query);

    // Si el usuario viene desde el carrito, existe una cookie con productos guardados.
    // Se aprovecha esa cookie para migrar ese carrito al usuario recién logueado.
	if($row && verify_and_upgrade_password($con, 'user_info', 'user_id', $row['user_id'], $row['password'], $password)){
            record_login_attempt('storefront', true);
            if (!session_regenerate_id(true)) {
                throw new RuntimeException('Unable to renew the authenticated session.');
            }
            $_SESSION['_session_created_at'] = time();
            $_SESSION['_session_last_activity'] = time();
            unset($_SESSION['admin_csrf_token']);
            $_SESSION["role"] = "user";
            unset($_SESSION["admin_name"], $_SESSION["admin_email"]);
            $_SESSION["uid"] = $row["user_id"];
            $_SESSION["name"] = $row["first_name"];
            $ip_add = getenv("REMOTE_ADDR");
			if (isset($_COOKIE["product_list"])) {
				$p_list = stripcslashes($_COOKIE["product_list"]);
				// Decodifica la cookie JSON guardada con los IDs de productos del carrito.
				$product_list = json_decode($p_list,true);
				for ($i=0; $i < count($product_list); $i++) { 
						// Verifica si ese producto ya está asociado a este usuario.
						$uid = $_SESSION["uid"];
						$product_id = filter_var($product_list[$i], FILTER_VALIDATE_INT);
						if ($product_id === false) {
							continue;
						}
						$verify_stmt = mysqli_prepare($con,
							"SELECT id FROM cart WHERE user_id=? AND p_id=?"
						);
						mysqli_stmt_bind_param($verify_stmt, "ii", $uid, $product_id);
						mysqli_stmt_execute($verify_stmt);
						$result = mysqli_stmt_get_result($verify_stmt);
						if(mysqli_num_rows($result) < 1){
							// Si es la primera vez que se agrega ese producto, se actualiza el usuario del carrito.
							$update_cart_stmt = mysqli_prepare($con,
								"UPDATE cart SET user_id=? WHERE ip_add=? AND user_id=-1"
							);
							mysqli_stmt_bind_param($update_cart_stmt, "is", $uid, $ip_add);
							mysqli_stmt_execute($update_cart_stmt);
						}else{
							// Si ya existe, elimina la copia antigua del carrito anónimo.
							$delete_stmt = mysqli_prepare($con,
								"DELETE FROM cart WHERE user_id=-1 AND ip_add=? AND p_id=?"
							);
							mysqli_stmt_bind_param($delete_stmt, "si", $ip_add, $product_id);
							mysqli_stmt_execute($delete_stmt);
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
            // Actualiza los registros del carrito y wishlist para asociarlos al usuario autenticado.
			$uid = $_SESSION["uid"];
			$cart_stmt = mysqli_prepare($con,
				"UPDATE cart SET user_id=? WHERE ip_add=? AND user_id=-1"
			);
			mysqli_stmt_bind_param($cart_stmt, "is", $uid, $ip_add);
			$cart_ok = mysqli_stmt_execute($cart_stmt);
			$wishlist_stmt = mysqli_prepare($con,
				"UPDATE wishlist SET user_id=? WHERE ip_add=? AND user_id=-1"
			);
			mysqli_stmt_bind_param($wishlist_stmt, "is", $uid, $ip_add);
			if($cart_ok){
				mysqli_stmt_execute($wishlist_stmt);
				echo "login_success";
				exit;
			}

		}else{
                // Si no encontró al usuario normal, intenta iniciar sesión como administrador.
                $email = $_POST["email"];
                $stmt_admin = mysqli_prepare($con, "SELECT * FROM admin_info WHERE admin_email = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt_admin, "s", $email);
                mysqli_stmt_execute($stmt_admin);
                $run_query_admin = mysqli_stmt_get_result($stmt_admin);
                $admin = mysqli_fetch_array($run_query_admin);

            // Si el administrador existe en la base, se crea su sesión.
            if($admin && verify_and_upgrade_password($con, 'admin_info', 'admin_id', $admin['admin_id'], $admin['admin_password'], $_POST["password"])){
                record_login_attempt('storefront', true);
                if (!session_regenerate_id(true)) {
                    throw new RuntimeException('Unable to renew the authenticated session.');
                }
                $_SESSION['_session_created_at'] = time();
                $_SESSION['_session_last_activity'] = time();
                unset($_SESSION['admin_csrf_token']);
                $_SESSION["role"] = "admin";
                $_SESSION["admin_name"] = $admin["admin_name"];
                $_SESSION["admin_email"] = $admin["admin_email"];
                $_SESSION["uid"] = $admin["admin_id"];
                $_SESSION["name"] = $admin["admin_name"];
                $ip_add = getenv("REMOTE_ADDR");

                // Si el login fue exitoso, responde con login_success_admin y redirige al panel del admin.
                echo "login_success_admin";
                exit;

            }else{
                record_login_attempt('storefront', false);
                // Si no existe ni usuario ni administrador, se informa al usuario que debe registrarse.
                echo "<span style='color:red;'>Please register before login..!</span>";
                exit();
            }
	}
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    record_login_attempt('storefront', false);
}
?>