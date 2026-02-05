<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if($_SESSION["role"] === "admin"){
        header("location: admin_dashboard.php");
    } else {
        header("location: user_dashboard.php");
    }
    exit;
}
require_once("config.php");

$name = $email = $password = "";
$name_err = $email_err = $password_err = $login_err = "";
$register_success = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["register"])){
        if(!isset($_POST["name"]) || empty(trim($_POST["name"]))){
            $name_err = "Please enter a name.";
        } elseif(!preg_match('/^[a-zA-Z0-9_\s]+$/', trim($_POST["name"]))){
            $name_err = "Name can only contain letters, numbers, spaces, and underscores.";
        } else{
            $sql = "SELECT user_id FROM users WHERE name = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "s", $param_name);
                $param_name = trim($_POST["name"]);
                
                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_store_result($stmt);
                    
                    if(mysqli_stmt_num_rows($stmt) == 1){
                        $name_err = "This name is already taken.";
                    } else{
                        $name = trim($_POST["name"]);
                    }
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        if(!isset($_POST["email"]) || empty(trim($_POST["email"]))){
            $email_err = "Please enter an email address.";
        } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        } else{
            $email = trim($_POST["email"]);
        }
        
        if(!isset($_POST["password"]) || empty(trim($_POST["password"]))){
            $password_err = "Please enter a password.";     
        } elseif(strlen(trim($_POST["password"])) < 6){
            $password_err = "Password must have at least 6 characters.";
        } else{
            $password = trim($_POST["password"]);
        }

        if(empty($name_err) && empty($email_err) && empty($password_err)){
            $sql = "INSERT INTO users (role, name, email, password) VALUES (?, ?, ?, ?)";
             
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "ssss", $param_role, $param_name, $param_email, $param_password);

                $param_role = "user";
                $param_name = $name;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                
                if(mysqli_stmt_execute($stmt)){
                    $register_success = "Registration successful! Please login.";
                    $name = $email = $password = "";
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    if(isset($_POST["login"])){
        if(!isset($_POST["login_email"]) || empty(trim($_POST["login_email"]))){
            $login_err = "Please enter your email.";
        } else{
            $email = trim($_POST["login_email"]);
        }
        
        if(!isset($_POST["login_password"]) || empty(trim($_POST["login_password"]))){
            $login_err = "Please enter your password.";
        } else{
            $password = trim($_POST["login_password"]);
        }
        
        if(empty($login_err)){
            $sql = "SELECT user_id, role, name, email, password FROM users WHERE email = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "s", $param_email);
                $param_email = $email;
                
                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_store_result($stmt);
                    
                    if(mysqli_stmt_num_rows($stmt) == 1){                    
                        mysqli_stmt_bind_result($stmt, $id, $role, $name, $db_email, $hashed_password);
                        if(mysqli_stmt_fetch($stmt)){
                            if($hashed_password && password_verify($password, $hashed_password)){
                                $_SESSION["loggedin"] = true;
                                $_SESSION["role"] = $role;
                                $_SESSION["user_id"] = $id;
                                $_SESSION["name"] = $name;
                                $_SESSION["email"] = $db_email;
                                
                                if($role === "admin"){
                                    header("location: admin_dashboard.php");
                                } else {
                                    header("location: user_dashboard.php");
                                }
                                exit;
                            } else{
                                $login_err = "Invalid email or password.";
                            }
                        }
                    } else{
                        $login_err = "Invalid email or password.";
                    }
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login & Register</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
    <style>
      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        background: linear-gradient(45deg,#0b3842, #AA895F);
        color: #fdfdfd;
        min-height: 100vh;
        display: grid;
        place-items: center;
        overflow-x: hidden;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      }

      .card {
        position: relative;
        overflow: hidden;
        width: 660px;
        height: 440px;
        border-radius: 16px;
        background: #2d5760;
        border: 8px solid #2d5760;
      }

      .card-bg {
        position: absolute;
        z-index: 2;
        top: 0;
        left: 0;
        bottom: 0;
        width: 50%;
        background: #0b3842;
        border-radius: 12px;
        translate: 0 0;
        transition: 0.65s ease-in-out;
      }

      .card-bg.login {
        translate: 100% 0;
      }

      .hero,
      .form {
        position: absolute;
        width: 50%;
        height: 100%;
        opacity: 0;
        visibility: hidden;
        transition: 0.65s ease-in-out;
      }

      .hero.active,
      .form.active {
        opacity: 1;
        visibility: visible;
      }

      .form.register {
        left: 50%;
      }

      .hero.login {
        left: 50%;
        translate: 100% 0;
      }

      .hero.login.active {
        translate: 0;
      }

      .hero.register {
        translate: -100% 0;
      }

      .hero.register.active {
        translate: 0;
      }

      .hero {
        z-index: 3;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        width: 50%;
        color: #f9f9f9;
        text-align: center;
        padding: 0 24px;
      }

      h2 {
        margin: 0;
        font-weight: 500;
      }

      .form.login {
        translate: 100% 0;
      }

      .form.login.active {
        translate: 0;
      }

      .form.register {
        translate: -100% 0;
      }

      .form.register.active {
        translate: 0;
      }

      .hero p {
        margin: 0 0 6px;
        color: #cac9d2;
        line-height: 1.45;
      }

      .hero button {
        padding: 12px 40px;
        border-radius: 32px;
        letter-spacing: 1px;
        font-family: inherit;
        color: inherit;
        border: 1px solid #f9f9f9;
        background: transparent;
        transition: 0.3s;
        cursor: pointer;
      }

      .hero button:hover {
        color: #ffffff;
        background: #1e525d;
      }

      .form {
        background: #2d5760;
        z-index: 1;
        width: 50%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 16px;
        padding: 32px;
      }

      .form h2 {
        font-size: 22px;
        text-align: center;
      }

      .form form > a {
        font-size: 14px;
        margin-top: 10px;
        color: #ffffff;
        text-decoration: none;
        cursor: pointer;
      }

      .form form > a:hover {
        text-decoration: underline;
      }

      .form form {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        width: 100%;
      }

      .form p {
        margin: 0 0 -8px;
        text-align: center;
        color: #cac9d2;
        font-size: 12px;
      }

      .form input {
        font-family: inherit;
        border-radius: 10px;
        border: 0;
        background: #0b3842;
        padding: 14px 12px;
        color: inherit;
        width: 100%;
      }

      .form input::placeholder {
        color: #cac9d2;
      }

      .form button[type="submit"] {
        border: 0;
        padding: 14px 0;
        border-radius: 32px;
        font-family: inherit;
        color: #f9f9f9;
        width: 160px;
        margin-top: 10px;
        background: #0b3842;
        cursor: pointer;
        transition: 0.3s;
      }

      .form button[type="submit"]:hover {
        background: #0a2f38;
      }

      .alert {
        padding: 10px 12px;
        border-radius: 8px;
        margin-bottom: 10px;
        font-size: 13px;
        width: 100%;
        text-align: center;
      }

      .alert-danger {
        background: rgba(220, 53, 69, 0.2);
        color: #ff6b7a;
        border: 1px solid rgba(220, 53, 69, 0.3);
      }

      .alert-success {
        background: rgba(40, 167, 69, 0.2);
        color: #5ff58a;
        border: 1px solid rgba(40, 167, 69, 0.3);
      }

      .toggle-link {
        font-size: 14px;
        margin-top: 10px;
        color: #ffffff;
        text-decoration: none;
        cursor: pointer;
        display: none;
      }

      .toggle-link:hover {
        text-decoration: underline;
      }

      /* Mobile Responsive Styles */
      @media (max-width: 768px) {
        .card {
          width: 90%;
          max-width: 400px;
          height: auto;
          min-height: 500px;
        }

        /* Hide the sliding background on mobile */
        .card-bg {
          display: none;
        }

        /* Hide hero sections on mobile */
        .hero {
          display: none !important;
        }

        /* Make forms full width and stack vertically */
        .form {
          position: relative;
          width: 100%;
          left: 0 !important;
          translate: 0 !important;
          padding: 40px 30px;
          opacity: 1;
          visibility: visible;
        }

        /* Hide inactive form on mobile */
        .form:not(.active) {
          display: none;
        }

        .form.active {
          display: flex;
        }

        /* Show toggle link on mobile */
        .toggle-link {
          display: block;
        }

        .form h2 {
          font-size: 24px;
          margin-bottom: 10px;
        }

        .form input {
          padding: 16px 14px;
          font-size: 16px;
        }

        .form button[type="submit"] {
          width: 100%;
          padding: 16px 0;
          font-size: 16px;
        }
      }
    </style>
  </head>
  <body>
    <div class="card">
      <div class="card-bg <?php echo (!empty($register_success) || !empty($login_err)) ? 'login' : ''; ?>"></div>
      

      <div class="hero register <?php echo (empty($register_success) && empty($login_err)) ? 'active' : ''; ?>">
        <h2>Welcome Back!</h2>
        <p>Already exploring Mauritius? Login to continue your adventure.</p>
        <button type="button" onclick="toggleView()">LOGIN</button>
      </div>
      
      <div class="form register <?php echo (empty($register_success) && empty($login_err)) ? 'active' : ''; ?>">
        <h2>Sign Up</h2>
        <?php 
        if(!empty($name_err)){
            echo '<div class="alert alert-danger">' . $name_err . '</div>';
        }
        if(!empty($email_err)){
            echo '<div class="alert alert-danger">' . $email_err . '</div>';
        }
        if(!empty($password_err)){
            echo '<div class="alert alert-danger">' . $password_err . '</div>';
        }
        if(!empty($register_success)){
            echo '<div class="alert alert-success">' . $register_success . '</div>';
        }
        ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
          <input type="text" name="name" placeholder="Full name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required />
          <input type="email" name="email" placeholder="Email address" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required />
          <input type="password" name="password" placeholder="Password" required />
          <button type="submit" name="register">SIGN UP</button>
          <a class="toggle-link" onclick="toggleView()">Already have an account? Login</a>
        </form>
      </div>
      

      <div class="hero login <?php echo (!empty($register_success) || !empty($login_err)) ? 'active' : ''; ?>">
        <h2>Discover Mauritius</h2>
        <p>Join us and explore the paradise island like never before.</p>

        <button type="button" onclick="toggleView()">SIGN UP</button>
      </div>

      <div class="form login <?php echo (!empty($register_success) || !empty($login_err)) ? 'active' : ''; ?>">
        <h2>Login</h2>
        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
          <input type="email" name="login_email" placeholder="Email" required />
          <input type="password" name="login_password" placeholder="Password" required />
          <button type="submit" name="login">LOGIN</button>
          <a class="toggle-link" onclick="toggleView()">Don't have an account? Sign up</a>
        </form>
      </div>
    </div>
    
    <script>
      function toggleView() {
        const cardBg = document.querySelector('.card-bg');
        const heroRegister = document.querySelector('.hero.register');
        const heroLogin = document.querySelector('.hero.login');
        const formRegister = document.querySelector('.form.register');
        const formLogin = document.querySelector('.form.login');
        

        cardBg.classList.toggle('login');
        heroRegister.classList.toggle('active');
        heroLogin.classList.toggle('active');
        formRegister.classList.toggle('active');
        formLogin.classList.toggle('active');
      }
    </script>
  </body>
</html>