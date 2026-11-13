<?php
require_once dirname(__DIR__, 2) . '/session_security.php';
require_once dirname(__DIR__, 2) . '/password_security.php';
require_once dirname(__DIR__) . '/admin/admin_access.php';
start_secure_session();
?>

<?php

// initializing variables
$name = "";
$username = "";
$usn = "";
$email    = "";
$errors = array();
$reg_date = date("Y/m/d");

// connect to the database
require_once __DIR__ . '/../../db.php';
$db = $con;


// REGISTER USER
if (isset($_POST['reg_user'])) {
  if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Only an authenticated administrator can create another administrator.');
  }
  require_admin_csrf_token();

  // receive all input values from the form
  $username = mysqli_real_escape_string($db, $_POST['admin_name']);
  $email = mysqli_real_escape_string($db, $_POST['admin_email']);
  $password_1 = $_POST['password_1'];
  $password_2 = $_POST['password_2'];

  // form validation: ensure that the form is correctly filled ...
  // by adding (array_push()) corresponding error unto $errors array
  if (empty($username)) { array_push($errors, "Username is required"); }
  if (empty($email)) { array_push($errors, "Email is required"); }
  if (empty($password_1)) { array_push($errors, "Password is required"); }
  if ($password_1 != $password_2) {
  array_push($errors, "The passwords do not match");
  }

  // first check the database to make sure
  // a user does not already exist with the same username and/or email
  $user_check_query = "SELECT * FROM admin_info WHERE admin_name='$username' OR admin_email='$email' LIMIT 1";
  $result = mysqli_query($db, $user_check_query);
  $user = mysqli_fetch_assoc($result);

  if ($user) { // if user exists
    if ($user['admin_name'] === $username) {
      array_push($errors, "Username already exists");
    }

    if ($user['admin_email'] === $email) {
      array_push($errors, "email already exists");
    }
  }

  // Finally, register user if there are no errors in the form
  if (count($errors) == 0) {
    $password = hash_project_password($password_1);

    $query = "INSERT INTO admin_info (admin_name, admin_email, admin_password)
          VALUES('$username', '$email', '$password')";
    mysqli_query($db, $query);
    $_SESSION['admin_name'] = $username;
    $_SESSION['admin_email'] = $email;
    $_SESSION['role'] = 'admin';

    $_SESSION['success'] = "You are now logged in";
    header('location: ./admin/');
    exit;
  }
}






if (isset($_POST['login_admin'])) {
  begin_login_attempt('admin-form');
  $admin_username = mysqli_real_escape_string($db, $_POST['admin_username']);
  $password = $_POST['password'];

  if (empty($admin_username)) {
    array_push($errors, "Username is required");
  }
  if (empty($password)) {
    array_push($errors, "Password is required");
  }

  if (count($errors) == 0) {
    $query = "SELECT * FROM admin_info WHERE admin_email='$admin_username' LIMIT 1";
    $results = mysqli_query($db, $query);
    $admin = mysqli_fetch_assoc($results);
    if ($admin && verify_and_upgrade_password($db, 'admin_info', 'admin_id', $admin['admin_id'], $admin['admin_password'], $password)) {
       record_login_attempt('admin-form', true);
       if (!session_regenerate_id(true)) {
         throw new RuntimeException('Unable to renew the authenticated session.');
       }
       $_SESSION['_session_created_at'] = time();
       $_SESSION['_session_last_activity'] = time();
       unset($_SESSION['admin_csrf_token']);
       $_SESSION['role'] = 'admin';
       $_SESSION['admin_email'] = $admin_username;
      $_SESSION['admin_name'] = $admin['admin_name'];
      $_SESSION['success'] = "You are now logged in";
      header('location: ./admin/');
      exit;
    }else {
      record_login_attempt('admin-form', false);
      array_push($errors, "Wrong username/password combination");
    }
  } else {
    record_login_attempt('admin-form', false);
  }
}


?>
