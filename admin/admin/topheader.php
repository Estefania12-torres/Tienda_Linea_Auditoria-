
<?php
require_once __DIR__ . '/admin_access.php';
require_admin_access();
?>
           <div class="main-panel">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-transparent navbar-absolute fixed-top " id="navigation-example">
                <div class="container-fluid">
                    <div class="navbar-wrapper">
                        <a class="navbar-brand" href="javascript:void(0)">Dashboard</a>
                        <div class="navbar-wrapper">
                        <a class="navbar-brand" style="color: #fff;">Welcome: <span style="font-weight:bold; color:#fff;"><?php  if (isset($_SESSION['admin_name'])) : ?><?php echo $_SESSION['admin_name']; ?>
             <?php endif ?></span></a>
                    </div>

                    </div>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" aria-controls="navigation-index" aria-expanded="false" aria-label="Toggle navigation" data-target="#navigation-example">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="navbar-toggler-icon icon-bar"></span>
                        <span class="navbar-toggler-icon icon-bar"></span>
                        <span class="navbar-toggler-icon icon-bar"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-end">
                        <form class="navbar-form">
                         
                        </form>
                        <ul class="navbar-nav">
                           
                          
                            <li class="nav-item">
                                <form method="post" action="logout.php" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-link" aria-label="Log out"><i class="fa fa-sign-out" aria-hidden="true"></i></button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>