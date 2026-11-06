
  <?php
 require_once dirname(__DIR__, 2) . '/session_security.php';
 require_once dirname(__DIR__, 2) . '/password_security.php';
 start_secure_session();
include("./includes/db.php");
if (isset($_POST['re_password']))
  {
    $email=$_SESSION['admin_email'];

  $old_pass = $_POST['old_pass'];
  $new_pass = $_POST['new_pass'];
  $re_pass = $_POST['re_pass'];
  $password_query = mysqli_query($con,"select * from admin_info where admin_email='$email'");
  $password_row = mysqli_fetch_array($password_query);
  if ($password_row && verify_and_upgrade_password($con, 'admin_info', 'admin_id', $password_row['admin_id'], $password_row['admin_password'], $old_pass))
    {
    if ($new_pass === $re_pass && $new_pass !== '')
      {
        $pass = hash_project_password($re_pass);
      $update_pwd = mysqli_prepare($con, 'UPDATE admin_info SET admin_password = ? WHERE admin_id = ?');
      if ($update_pwd === false || !mysqli_stmt_bind_param($update_pwd, 'ss', $pass, $password_row['admin_id']) || !mysqli_stmt_execute($update_pwd)) {
        throw new RuntimeException('Unable to update the administrator password.');
      }
      mysqli_stmt_close($update_pwd);
      echo "<script>alert('Update Sucessfully'); </script>";
      }
      else
      {
      echo "<script>alert('Your new and Retype Password is not match'); </script>";
      }
    }
    else
    {
    echo "<script>alert('Your old password is wrong'); </script>";
    }
  }
 
include "sidenav.php";
include "topheader.php";

?>
      <!-- End Navbar -->
   <div class="content">
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-8">
              <div class="card">
                <div class="card-header card-header-primary">
                  <h4 class="card-title">Edit Profile</h4>
                  <p class="card-category">Complete your profile</p>
                </div>
                <div class="card-body">
                  <form method="post" action="profile.php">
                    <div class="row">
                      <div class="col-md-5">
                        <div class="form-group bmd-form-group">
                          <label class="bmd-label-floating">
                            <?php  if (isset($_SESSION['admin_name'])) : ?><?php echo $_SESSION['admin_name']; ?>
             <?php endif ?>
                          
                        </label>
                          <input type="text" class="form-control" disabled="">
                        </div>
                      </div>
                     <div class="col-md-4">
                        <div class="form-group bmd-form-group">
                          <label class="bmd-label-floating">enter old password</label>
                          <input type="password" class="form-control" name="old_pass" id="old-password">
                        </div>
                      </div>
                    
                  
                      <div class="col-md-4">
                        <div class="form-group bmd-form-group">
                          <label class="bmd-label-floating">Change Password Here</label>
                          <input type="password" class="form-control" name="new_pass" id="new-password">
                        </div>
                      </div>
                     
                      <div class="col-md-4">
                        <div class="form-group bmd-form-group">
                          <label class="bmd-label-floating">confirm Password Here</label>
                          <input type="password" class="form-control" name="re_pass" id="confirm-password">
                        </div>
                      </div>
               
                    <button class="btn btn-primary pull-right" type="submit" name="re_password">Update Profile</button>
                   
                    <div class="clearfix"></div>
                  </form>
                </div>
              </div>
            </div>
         
          </div>
        </div>
      </div>
      <?php
include "footer.php";
?>