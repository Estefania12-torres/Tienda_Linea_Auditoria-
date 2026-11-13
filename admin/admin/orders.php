
<?php
require_once __DIR__ . '/admin_access.php';
require_admin_access();
include("./includes/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    require_admin_csrf_token();
    $order_id = filter_var(isset($_POST['order_id']) ? $_POST['order_id'] : null, FILTER_VALIDATE_INT);
    if ($order_id === false || $order_id === null || $order_id < 1) {
        http_response_code(400);
        exit('Invalid order identifier.');
    }

    $delete_statement = mysqli_prepare($con, 'DELETE FROM orders WHERE order_id = ?');
    if (
        $delete_statement === false
        || !mysqli_stmt_bind_param($delete_statement, 'i', $order_id)
        || !mysqli_stmt_execute($delete_statement)
    ) {
        throw new RuntimeException('Unable to delete the requested order.');
    }
    mysqli_stmt_close($delete_statement);
    header('Location: orders.php');
    exit;
}

$csrf_token = admin_csrf_token();

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
          <!-- your content here -->
          <div class="col-md-14">
            <div class="card ">
              <div class="card-header card-header-primary">
                <h4 class="card-title">Orders  / Page <?php echo $page;?> </h4>
              </div>
              <div class="card-body">
                <div class="table-responsive ps">
                  <table class="table table-hover tablesorter " id="">
                    <thead class=" text-primary">
                      <tr><th>Customer Name</th><th>Products</th><th>Contact | Email</th><th>Address</th><th>Details</th><th>Shipping</th><th>Time</th>
                    </tr></thead>
                    <tbody>
                      <?php 
                        $result=mysqli_query($con,"select order_id, product_title, first_name, mobile, email, address1, address2, product_price,address2, qty from orders,products,user_info where orders.product_id=products.product_id and user_info.user_id=orders.user_id Limit $page1,10")or die ("query 1 incorrect.....");

                        while(list($order_id,$p_names,$cus_name,$contact_no,$email,$address,$country,$details,$zip_code,$time,$quantity)=mysqli_fetch_array($result))
                        {	
                        echo "<tr><td>$cus_name</td><td>$p_names</td><td>$email<br>$contact_no</td><td>$address<br>ZIP: $zip_code<br>$country</td><td>$details</td><td>$quantity</td><td>$time</td>
                        <td><form method='post' action='orders.php'>
                        <input type='hidden' name='csrf_token' value='" . htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') . "'>
                        <input type='hidden' name='action' value='delete'>
                        <input type='hidden' name='order_id' value='" . (int) $order_id . "'>
                        <button class='btn btn-danger' type='submit'>Delete</button>
                        </form></td></tr>";
                        }
                        ?>
                    </tbody>
                  </table>
                  
                <div class="ps__rail-x" style="left: 0px; bottom: 0px;"><div class="ps__thumb-x" tabindex="0" style="left: 0px; width: 0px;"></div></div><div class="ps__rail-y" style="top: 0px; right: 0px;"><div class="ps__thumb-y" tabindex="0" style="top: 0px; height: 0px;"></div></div></div>
              </div>
            </div>
          </div>
          
        </div>
      </div>
      <?php
include "footer.php";
?>