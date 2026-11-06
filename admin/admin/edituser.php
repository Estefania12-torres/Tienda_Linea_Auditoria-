
    <?php
require_once dirname(__DIR__, 2) . '/session_security.php';
require_once dirname(__DIR__, 2) . '/password_security.php';
start_secure_session();
include("../../db.php");
$user_id=$_REQUEST['user_id'];


if(isset($_POST['btn_save'])) 
{

$first_name=$_POST['first_name'];
$last_name=$_POST['last_name'];
$email=$_POST['email'];
$user_password = isset($_POST['password']) ? $_POST['password'] : '';

if ($user_password === '') {
  $update_statement = mysqli_prepare($con, 'UPDATE user_info SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?');
  if ($update_statement === false || !mysqli_stmt_bind_param($update_statement, 'ssss', $first_name, $last_name, $email, $user_id) || !mysqli_stmt_execute($update_statement)) {
    throw new RuntimeException('Unable to update the user.');
  }
} else {
  $password_hash = hash_project_password($user_password);
  $update_statement = mysqli_prepare($con, 'UPDATE user_info SET first_name = ?, last_name = ?, email = ?, password = ? WHERE user_id = ?');
  if ($update_statement === false || !mysqli_stmt_bind_param($update_statement, 'sssss', $first_name, $last_name, $email, $password_hash, $user_id) || !mysqli_stmt_execute($update_statement)) {
    throw new RuntimeException('Unable to update the user and password.');
  }
  $backup_statement = mysqli_prepare($con, 'UPDATE user_info_backup SET password = ? WHERE user_id = ?');
  if ($backup_statement === false || !mysqli_stmt_bind_param($backup_statement, 'ss', $password_hash, $user_id) || !mysqli_stmt_execute($backup_statement)) {
    throw new RuntimeException('Unable to update the backup password.');
  }
  mysqli_stmt_close($backup_statement);
}
mysqli_stmt_close($update_statement);

header("location: manageuser.php");
mysqli_close($con);
}
include "sidenav.php";
include "topheader.php";
?>
      <!-- End Navbar -->
      <div class="content">
        <div class="container-fluid">
        <div class="col-md-5 mx-auto">
            <div class="card">
              <div class="card-header card-header-primary">
                <h5 class="title">Edit User</h5>
              </div>
              <form action="edituser.php" name="form" method="post" enctype="multipart/form-data">
              <div class="card-body">
                
                  <input type="hidden" name="user_id" id="user_id" value="<?php echo $user_id;?>" />
                    <div class="col-md-12 ">
                      <div class="form-group">
                        <label>First name</label>
                        <input type="text" id="first_name" name="first_name"  class="form-control" value="<?php echo $first_name; ?>" >
                      </div>
                    </div>
                    <div class="col-md-12 ">
                      <div class="form-group">
                        <label>Last name</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo $last_name; ?>" >
                      </div>
                    </div>
                    <div class="col-md-12 ">
                      <div class="form-group">
                        <label for="exampleInputEmail1">Email address</label>
                        <input type="email"  id="email" name="email" class="form-control" value="<?php echo $email; ?>">
                      </div>
                    </div>
                    <div class="col-md-12 ">
                      <div class="form-group">
                        <label >Password</label>
                        <input type="password" name="password" id="password" class="form-control" autocomplete="new-password" placeholder="Leave blank to keep current password">
                      </div>
                    </div>
                  
                  
                  
                
              </div>
              <div class="card-footer">
                <button type="submit" id="btn_save" name="btn_save" class="btn btn-fill btn-primary">Update</button>
              </div>
              </form>    
            </div>
          </div>
         
          
        </div>
      </div>
      <?php
include "footer.php";
?>