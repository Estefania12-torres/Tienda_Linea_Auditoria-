<?php
require_once __DIR__ . '/admin_access.php';
require_admin_access();
include("../../db.php");
error_reporting(0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    require_admin_csrf_token();
    $product_id = filter_var(isset($_POST['product_id']) ? $_POST['product_id'] : null, FILTER_VALIDATE_INT);
    if ($product_id === false || $product_id === null || $product_id < 1) {
        http_response_code(400);
        exit('Invalid product identifier.');
    }

    delete_admin_product($con, $product_id);
    header('Location: products_list.php');
    exit;
}

$csrf_token = admin_csrf_token();
///pagination

$requested_page = isset($_GET['page']) ? $_GET['page'] : '1';
$page = ctype_digit((string) $requested_page) && (int) $requested_page > 0
    ? (int) $requested_page
    : 1;

$page1 = ($page - 1) * 10;
include "sidenav.php";
include "topheader.php";
?>
      <!-- End Navbar -->
      <div class="content">
        <div class="container-fluid">
        
        
         <div class="col-md-14">
            <div class="card ">
              <div class="card-header card-header-primary">
                <h4 class="card-title"> Products List</h4>
                
              </div>
              <div class="card-body">
                <div class="table-responsive ps">
                  <table class="table tablesorter " id="page1">
                    <thead class=" text-primary">
                      <tr><th>Image</th><th>Name</th><th>Price</th><th>
                      <?php
                      if ($_SESSION['role'] != 'e') {
                        echo'
                            <a class=" btn btn-primary" href="add_products.php">Add New</a>
                        ';
                    
                      }
                      ?>
	                  </th></tr></thead>
                    <tbody>
                      <?php 

                        $result=mysqli_query($con,"select product_id,product_image, product_title,product_price from products  where  product_cat=1 or product_cat=2 or product_cat=3 Limit $page1,12")or die ("query 1 incorrect.....");

                        while(list($product_id,$image,$product_name,$price)=mysqli_fetch_array($result))
                        {
                          $substr = substr($product_name,0,36);
                          // $price = round($price*0.014,2);
                        echo "<tr><td><img src='../../product_images/$image' style='width:50px; height:50px; border:groove #000'></td><td>$substr</td>
                        <td>RS $price</td>
                        <td> ";
                    
                      if ($_SESSION['role'] != 'e') {
                        echo"
                        <a class=' btn btn-success' href='edit_product.php?product_id=$product_id'>Edit</a>
                        <form method='post' action='products_list.php'>
                        <input type='hidden' name='csrf_token' value='" . htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') . "'>
                        <input type='hidden' name='action' value='delete'>
                        <input type='hidden' name='product_id' value='" . (int) $product_id . "'>
                        <button class='btn btn-danger' type='submit'>Delete</button>
                        </form>
                        ";
                    
                      }
                    echo "
                        
                        </td></tr>";
                        }

                        ?>
                    </tbody>
                  </table>
                <div class="ps__rail-x" style="left: 0px; bottom: 0px;"><div class="ps__thumb-x" tabindex="0" style="left: 0px; width: 0px;"></div></div><div class="ps__rail-y" style="top: 0px; right: 0px;"><div class="ps__thumb-y" tabindex="0" style="top: 0px; height: 0px;"></div></div></div>
              </div>
            </div>
            <nav aria-label="Page navigation example">
              <ul class="pagination">
                <li class="page-item">
                  <a class="page-link" href="#" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                    <span class="sr-only">Previous</span>
                  </a>
                </li>
                 <?php 
//counting paging

                $paging=mysqli_query($con,"select product_id,product_image, product_title,product_price from products");
                $count=mysqli_num_rows($paging);

                $a=$count/10;
                $a=ceil($a);
                
                for($b=1; $b<=$a;$b++)
                {
                ?> 
                <li class="page-item"><a class="page-link" href="products_list.php?page=<?php echo $b;?>"><?php echo $b." ";?></a></li>
                <?php	
}
?>
                <li class="page-item">
                  <a class="page-link" href="#" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                    <span class="sr-only">Next</span>
                  </a>
                </li>
              </ul>
            </nav>
            
           

          </div>
          
          
        </div>
      </div>
      <?php
include "footer.php";
?>