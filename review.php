<?php
// Se inicia la sesión para identificar al usuario que está dejando la reseña.
session_start();

// Se conecta con la base de datos para guardar la valoración del producto.
include "db.php";

// Se valida si se envió la acción de guardar una reseña.
if (isset($_POST["review"])) {

	// Se reciben los datos del formulario de review.
	$name = $_POST["name"];
	$email = $_POST["email"];
	$review = $_POST['review'];
	$rating = $_POST['rating'];
	$product_id =$_POST['product_id'];
    $datetime =  date('Y-m-d H:i:s');

	// Se busca si ya existe una reseña de ese email para ese producto.
	$sql = "SELECT review_id FROM reviews WHERE email = '$email' AND product_id = '$product_id' ";
	$check_query = mysqli_query($con,$sql);
	$count_email = mysqli_num_rows($check_query);
	if($count_email > 0){
		echo "
				<div class='alert alert-danger'>
					<a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
					<b>Multiple reviews are not Allowed</b>
				</div>
			";
		exit();
	}else{
		// Si no existe reseña previa, la inserta en la tabla de reviews.
		$sql = "INSERT INTO `reviews` (`review_id`, `product_id`, `name`, `email`, `review`, `datetime`, `rating`) 
		VALUES  (NULL, '$product_id','$name', '$email', 
		'$review','$datetime', '$rating')";
		
		if(mysqli_query($con,$sql)){
			echo "Thanks for Better reach ";
			echo "<script> location.href='product.php?q=$product_id'; </script>";
			exit;
		}else {
			echo "something went wrong";
		}
	}
}
?>






















































