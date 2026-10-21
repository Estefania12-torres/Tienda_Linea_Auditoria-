<?php
// Inicia la sesión para poder acceder a las variables guardadas del usuario.
session_start();

// Elimina la sesión del usuario autenticado.
unset($_SESSION["uid"]);

// Elimina el nombre del usuario de la sesión.
unset($_SESSION["name"]);

// Guarda la página desde la cual el usuario llegó antes de cerrar sesión.
// Esto permite volver a la última página visitada si existe.
$BackToMyPage = $_SERVER['HTTP_REFERER'];

// Si existe una referencia previa, redirige a esa página.
if(isset($BackToMyPage)) {
    header('Location: '.$BackToMyPage);
} else {
    // Si no existe, redirige a la página principal de la tienda.
    header('Location: index.php'); // default page
}
?>
