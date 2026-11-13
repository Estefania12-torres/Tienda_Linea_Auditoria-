
<?php
require_once __DIR__ . '/admin_access.php';
require_admin_access();
include("../../db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    require_admin_csrf_token();
    $user_id = filter_var(isset($_POST['user_id']) ? $_POST['user_id'] : null, FILTER_VALIDATE_INT);
    if ($user_id === false || $user_id === null || $user_id < 1) {
        http_response_code(400);
        exit('Invalid user identifier.');
    }

    $delete_statement = mysqli_prepare($con, 'DELETE FROM user_info WHERE user_id = ?');
    if (
        $delete_statement === false
        || !mysqli_stmt_bind_param($delete_statement, 'i', $user_id)
        || !mysqli_stmt_execute($delete_statement)
    ) {
        throw new RuntimeException('Unable to delete the requested user.');
    }
    mysqli_stmt_close($delete_statement);
    header('Location: manageuser.php');
    exit;
}

$csrf_token = admin_csrf_token();
include "sidenav.php";
include "topheader.php";
?>
      <!-- End Navbar -->
      <div class="content">
        <div class="container-fluid">
         <div class="col-md-14">
            <div class="card ">
              <div class="card-header card-header-primary">
                <h4 class="card-title">Manage User</h4>
              </div>
              <div class="card-body">
                <div class="table-responsive ps">
                  <table class="table tablesorter table-hover" id="">
                    <thead class=" text-primary">
                      <tr><th>User id</th>
                        <th>First_name</th>
                        <th>last_name</th>
                        <th>Email</th>
                       
                <th>mobile</th>
                <th>city</th>
                <th>Address</th>
	<th><a href="addsuppliers.php" class="btn btn-success">Add New</a></th>
                    </tr></thead>
                    <tbody>
                      <?php 
                        $result=mysqli_query($con,"select user_id,first_name,last_name, email,mobile,address1,address2 from user_info")or die ("query 2 incorrect.......");

                        while(list($user_id,$user_name,$user_last,$email,$mobile,$address1,$address2)=
                        mysqli_fetch_array($result))
                        {
                        echo "<tr>
                        <td>" . htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8') . "</td>
                         <td>" . htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') . "</td>
                          <td>" . htmlspecialchars($user_last, ENT_QUOTES, 'UTF-8') . "</td>
                           <td>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</td>
                         <td>" . htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8') . "</td>
                          <td>" . htmlspecialchars($address1, ENT_QUOTES, 'UTF-8') . "</td>
                           <td>" . htmlspecialchars($address2, ENT_QUOTES, 'UTF-8') . "</td>";
                        echo "<td><form method='post' action='manageuser.php'>
                        <input type='hidden' name='csrf_token' value='" . htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') . "'>
                        <input type='hidden' name='action' value='delete'>
                        <input type='hidden' name='user_id' value='" . (int) $user_id . "'>
                        <button class='btn btn-danger' type='submit'>Delete</button>
                        </form></td></tr>";
                        }
                        mysqli_close($con);
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