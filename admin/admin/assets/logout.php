
<?php

//logout.php

require_once dirname(__DIR__, 3) . '/session_security.php';
start_secure_session();

session_destroy();

header('location:login_form.php');

?>