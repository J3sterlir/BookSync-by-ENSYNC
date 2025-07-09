<?php
include("database.php");

// Initialize session FIRST
session_start();

try {
    $conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
    if (!$conn) {
        throw new mysqli_sql_exception("Connection failed: " . mysqli_connect_error());
    }
    //echo "<div id='connected'>Database Status: Online</div>";
} catch (mysqli_sql_exception $e) {
    echo "<div id='disconnected'>Database Connection Error: " . $e->getMessage() . "<br></div>";
    exit(); // Stop execution if the database connection fails
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>BookSync Login</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/Signin.css">
</head>
<body>

    <header>
        <nav class="adminnav" style="padding-top: 20px; padding-bottom: 20px; background: #ffffff;
background: linear-gradient(180deg,rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 1) 51%, rgba(255, 255, 255, 0.58) 100%); color: var(--background-color); width: auto; text-align: center;">
            <h1>BookSync Management System</h1>
        </nav>
    </header>

    <div class="MainContainer">
        <div class="login-box">
            <br><br>
            <h2 class="Login">LOGIN</h2>
            <hr>
            <br>
            <form id="EmployeeLogin" action="" method="post" novalidate>
                <label for="Username">Username:</label>
                <input type="text" id="Username" name="Username" placeholder="Username" required><br><br>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="●●●●●●●●●●" required>
                
                <br><br>
                <label class="label-reg" for="password">Don't Have An Account? <a href="Register.php"><b>Register</b></a></label>
                <input type="submit" class="btn" name="login" value="Log In">
                <br><br>
            </form>
        </div>
    </div>
</body>
</html>

<?php
if (isset($_POST["login"])) {
    // Sanitize inputs
    $username = filter_input(INPUT_POST, "Username", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password'];
    
    // Prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT users.*, user_types.type as role 
                           FROM users 
                           JOIN user_types ON users.type_id = user_types.id 
                           WHERE username = ? AND is_active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!empty($username) && !empty($password)) {
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password using hashing
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['first_name'] = $user['name']; // Changed to 'name' based on your earlier structure
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'employee':
                        header("Location: home.php");
                        break;
                    case 'client':
                        header("Location: clientside/clientview.php");
                        break;
                    case 'admin':
                        header("Location: adminside/adminpage.php");
                        break;
                    default:
                        echo '<div id="Missing">Unknown user role</div>';
                }
                exit();
            } else {
                echo '<div id="Missing">Invalid Password</div>';
            }
        } else {
            echo '<div id="Missing">Username not found or account inactive</div>';
        }
    } else if (empty($username) && empty($password)) {
        echo '<div id="Missing">Missing Username & Password</div>';
    } else if (empty($username)) {
        echo '<div id="Missing">Missing Username</div>';
    } else if (empty($password)) {
        echo '<div id="Missing">Missing Password</div>';
    }
    
    $stmt->close();
}

// Close connection to the database
$conn->close();
?>
