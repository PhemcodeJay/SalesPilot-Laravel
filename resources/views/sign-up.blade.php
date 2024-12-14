

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>Register</title>
      
      <!-- Favicon -->
      <link rel="shortcut icon" href="http://localhost:8000/assets/images/favicon.ico" />
      <link rel="stylesheet" href="http://localhost:8000/assets/css/backend-plugin.min.css">
      <link rel="stylesheet" href="http://localhost:8000/assets/css/backend.css?v=1.0.0">
      <link rel="stylesheet" href="http://localhost:8000/assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
      <link rel="stylesheet" href="http://localhost:8000/assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
      <link rel="stylesheet" href="http://localhost:8000/assets/vendor/remixicon/fonts/remixicon.css">  </head>
  <body class=" ">
    <!-- loader Start -->
    <div id="loading">
          <div id="loading-center">
          </div>
    </div>
    <!-- loader END -->
    
      <div class="wrapper">
      <section class="login-content">
         <div class="container">
            <div class="row align-items-center justify-content-center height-self-center">
               <div class="col-lg-8">
                  <div class="card auth-card">
                     <div class="card-body p-0">
                        <div class="d-flex align-items-center auth-content">
                           <div class="col-lg-7 align-self-center">
                              <div class="p-3">
                                 <h2 class="mb-2">Sign Up</h2>
                                 <p>Create your SalesPilot account.</p>
                                 <form role="form" id="registrationForm" method="POST" action="sign-up.php" autocomplete="off">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <div class="input-group input-group-outline mb-3">
                                      <input type="text" class="form-control" id="Username" name="Username" placeholder="Enter your username" required>
                                    </div>
                                    <div class="input-group input-group-outline mb-3">
                                      <input type="password" class="form-control" id="Password" name="Password" placeholder="Enter your password" required>
                                    </div>
                                    <div class="input-group input-group-outline mb-3">
                                      <input type="password" class="form-control" id="confirmpassword" name="confirmpassword" placeholder="Confirm your password" required>
                                    </div>
                                    <div class="input-group input-group-outline mb-3">
                                      <input type="email" class="form-control" id="Email" name="Email" placeholder="Enter your email" required>
                                    </div>
                                    <div class="form-check form-check-info text-start ps-0">
                                      <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" required>
                                      <label class="form-check-label" for="flexCheckDefault">
                                        I agree to the <a href="javascript:;" class="text-dark font-weight-bolder">Terms and Conditions</a>
                                      </label>
                                    </div>
                                    <div class="text-center">
                                      <button type="submit" name="signup" class="btn btn-lg bg-gradient-primary btn-lg w-100 mt-4 mb-0">Sign Up</button>
                                    </div>
                                  </form>
                                  <div class="text-center">
                                  <button type="button" id="resetButton" class="btn bg-gradient-primary w-100 my-4 mb-2" onclick="window.location.href='http://localhost:8000/loginpage.php'">Login</button>

                                    </div>
                              </div>
                           </div>
                           <div class="col-lg-5 content-right">
                              <img src="http://localhost:8000/assets/images/login/01.png" class="img-fluid image-right" alt="">
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      </div>
    
    <!-- Backend Bundle JavaScript -->
    <script src="http://localhost:8000/assets/js/backend-bundle.min.js"></script>
    
    <!-- Table Treeview JavaScript -->
    <script src="http://localhost:8000/assets/js/table-treeview.js"></script>
    
    <!-- app JavaScript -->
    <script src="http://localhost:8000/assets/js/app.js"></script>
  </body>
</html>