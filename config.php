
<?php
require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/password_security.php';
start_secure_session();

// initializing variables
$username = "";
$email    = "";
$errors = array(); 

// connect to the database
require_once __DIR__ . '/env.php';

define('DB_SERVER', project_env('AUTH_DB_HOST'));
define('DB_USERNAME', project_env('AUTH_DB_USER'));
define('DB_PASSWORD', project_env('AUTH_DB_PASSWORD'));
define('DB_DATABASE', project_env('AUTH_DB_NAME'));
$db = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
if (!$db) {
    die("Connection failed: " . mysqli_connect_error());
}

// REGISTER USER
if (isset($_POST['reg_user'])) {
  // receive all input values from the form
  $username = mysqli_real_escape_string($db, $_POST['username']);
  $email = mysqli_real_escape_string($db, $_POST['email']);
  $password_1 = $_POST['password_1'];
  $password_2 = $_POST['password_2'];

  // form validation: ensure that the form is correctly filled ...
  // by adding (array_push()) corresponding error unto $errors array
  if (empty($username)) { array_push($errors, "Username is required"); }
  if (empty($email)) { array_push($errors, "Email is required"); }
  if (empty($password_1)) { array_push($errors, "Password is required"); }
  if ($password_1 != $password_2) {
	array_push($errors, "The two passwords do not match");
  }

  // first check the database to make sure 
  // a user does not already exist with the same username and/or email
  $user_check_query = "SELECT * FROM register WHERE Name='$username' OR email='$email' LIMIT 1";
  $result = mysqli_query($db, $user_check_query);
  $user = mysqli_fetch_assoc($result);
  
  if ($user) { // if user exists
    if ($user['Name'] === $username) {
      array_push($errors, "Username already exists");
    }

    if ($user['email'] === $email) {
      array_push($errors, "email already exists");
    }
  }

  // Finally, register user if there are no errors in the form
  if (count($errors) == 0) {
    $password = hash_project_password($password_1);

  	$query = "INSERT INTO register (Name, email, password) 
  			  VALUES('$username', '$email', '$password')";
  	mysqli_query($db, $query);
  	$_SESSION['Name'] = $username;
  	$_SESSION['success'] = "You are now logged in";
  	header('location: index.php');
  }
}
if (isset($_POST['login_user'])) {
  begin_login_attempt('legacy-user');
  $username = mysqli_real_escape_string($db, $_POST['email']);
  $password = $_POST['password'];

  if (empty($username)) {
  	array_push($errors, "email is required");
  }
  if (empty($password)) {
  	array_push($errors, "Password is required");
  }

  if (count($errors) == 0) {
    $query = "SELECT * FROM register WHERE email='$username' LIMIT 1";
    $results = mysqli_query($db, $query);
    $user = mysqli_fetch_assoc($results);
    if ($user && verify_and_upgrade_password($db, 'register', 'email', $user['email'], $user['password'], $password)) {
      record_login_attempt('legacy-user', true);
      session_regenerate_id(true);
  	  $_SESSION['email'] = $username;
  	  $_SESSION['success'] = "You are now logged in";
  	  header('location: index.php');
  	}else {
        record_login_attempt('legacy-user', false);
  		array_push($errors, "Wrong username/password combination");
  	}
  } else {
    record_login_attempt('legacy-user', false);
  }
}

?>