
<?php
// Se inicia la sesión para mantener el contexto del usuario actual.
session_start();

// Se obtiene la IP del cliente para gestionar carritos anónimos si el usuario no ha iniciado sesión.
$ip_add = getenv("REMOTE_ADDR");

// Conexión a la base de datos.
include "db.php";

// Si se solicita mostrar la sección completa de reseñas y valoración.
if(isset($_POST["review_action"])){
    $p_id = $_POST["proId"];

    // Se imprime el bloque del resumen general de valoraciones del producto.
    echo'<div class="col-md-3">
        <div id="rating">';  

            // Consulta para obtener el promedio y la cantidad total de reseñas del producto.
            $rating_query = "SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS count FROM reviews WHERE product_id='$p_id'";
            $run_query = mysqli_query($con,$rating_query);
            $row = mysqli_fetch_array($run_query);
            $r_count = $row['count'];

            // Si existen reseñas, se muestra el promedio y las estrellas completas/vacías.
            if($row > 0){
                $avg_count=$row["avg_rating"];
                echo '<div class="rating-avg">
                <span>'.$avg_count.'</span>
                <div class="rating-stars">';
                    $i=1;
                    while($i <= round($avg_count)){
                        $i++;
                        echo'
                        <i class="fa fa-star"></i>';
                    }
                    $i=1;
                    while($i <= 5-round($avg_count)){
                        $i++;
                        echo'
                        <i class="fa fa-star-o empty"></i>';
                    }
                echo'
                </div>
            </div>';
            }

            // Se calcula la distribución de reseñas por estrellas del 1 al 5.
            echo'<ul class="rating">';
                $stars=array();
                $total_stars= 0;
                $rat=1;
                while ($rat <= 5){
                        $rating_query = "SELECT  COUNT(*) as count FROM reviews WHERE product_id='$p_id' AND rating='$rat'";
                        $run_query = mysqli_query($con,$rating_query);
                        $row = mysqli_fetch_array($run_query);
                        if($row > 0){
                            $stars[$rat]= $row['count'];
                            $total_stars += $stars[$rat];
                        }
                        $rat++;
                }

                $i=5;
                while ($i >= 1){
                    if($total_stars != 0){
                        $percent = $stars[$i] *100/$total_stars;
                    }else{
                        $percent = 0;
                    }
                    echo'
                        <li>
                        <div class="rating-stars">';
                        $j=1;
                        while($j <= $i){
                            $j++;
                            echo'
                            <i class="fa fa-star"></i>';
                        }
                        $j=1;
                        while($j <= 5-$i){
                            $j++;
                            echo'
                            <i class="fa fa-star-o empty"></i>';
                        }
                    echo'
                        </div>
                        <div class="rating-progress">
                            <div style="width: '.$percent.'%;"></div>
                        </div>
                        <span class="sum">'.$stars[$i].'</span>
                    </li>
                    ';
                    $i--;
                }
            echo'</ul>
        </div>
    </div>
    <!-- /Rating -->

    <!-- Reviews -->
    <div class="col-md-6">
        <div id="reviews">
            <ul class="reviews" id="reviews">
            ';

            // Consulta para recuperar todas las reseñas del producto.
            $product_query = "SELECT * FROM reviews WHERE product_id='$p_id'";
            $run_query = mysqli_query($con,$product_query);
            if(mysqli_num_rows($run_query) > 0){
                while($row = mysqli_fetch_array($run_query)){
                    $review_id = $row['review_id'];
                    $rating = $row['rating'];
                    $review = $row['review'];
                    $name = $row['name'];
                    $email = $row['email'];
                    $date = $row['datetime'];
                    $unstar = 5-$rating;
                    echo '
                    <li>
                        <div class="review-heading">
                            <h5 class="name">'.$name.'</h5>
                            <p class="date">'.$date.'</p>
                            <div class="review-rating">';
                            $i=1;
                            while($i <= $rating){
                                $i++;
                                echo'
                                <i class="fa fa-star"></i>';
                            }
                            $i=1;
                            while($i <= $unstar){
                                $i++;
                                echo'
                                <i class="fa fa-star-o empty"></i>';
                            }
                            echo'	</div>
                    </div>
                        <div class="review-body">
                            <p>'.$review.'</p>
                        </div>
                    </li>';
                }
            }

            echo'</ul>';

            // Si hay reseñas, se muestran paginación; si no, se avisa que el primer usuario puede calificar.
            if ($r_count > 0){
                echo'<ul class="reviews-pagination">
                    <li class="active">1</li>
                    <li><a href="#">2</a></li>
                    <li><a href="#">3</a></li>
                    <li><a href="#">4</a></li>
                    <li><a href="#"><i class="fa fa-angle-right"></i></a></li>
                </ul>';
            }else{
                echo'<p>no Reviews yet be the first person to rate the product</p>';
            }

        echo'</div>
    </div>';
}

// Si solo se necesita mostrar una vista corta de rating para el producto.
if(isset($_POST["rating_reviews"])){
    $p_id = $_POST["proId"]; 
    $rating_query = "SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS count FROM reviews WHERE product_id='$p_id'";
    $run_query = mysqli_query($con,$rating_query);
    $row = mysqli_fetch_array($run_query);
    $r_count = $row['count'];
    if($row > 0){
        $avg_count=$row["avg_rating"];
        echo '<div class="product-rating">';
            $i=1;
            while($i <= round($avg_count)){
                $i++;
                echo'
                <i class="fa fa-star"></i>';
            }
            $i=1;
            while($i <= 5-round($avg_count)){
                $i++;
                echo'
                <i class="fa fa-star-o empty"></i>';
            }
        echo'
    </div>
    <a class="review-link" href="#review-form">'.$r_count.' Review(s) | Add your review</a>
    ';
    }
}
?>