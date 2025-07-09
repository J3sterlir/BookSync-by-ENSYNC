<?php
include("database.php");
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
session_start();

// Check if user is logged in (if user was assigned a session that macthes their credentials)
if (!isset($_SESSION['user_id']) || 
    !isset($_SESSION['username']) || 
    !isset($_SESSION['logged_in']) || 
    !isset($_SESSION['role']) ||  // Changed from 'roles' to 'role' to match our schema
    $_SESSION['logged_in'] !== true) {
    
    // Destroy invalid session
    session_unset();
    session_destroy();
    
    // Redirect to login with error message
    header("Location: Login.php?error=session_invalid");
    exit();
}

// HEADER NAV
include('Component/nav-head.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>BookSync Profile</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/Dashboard.css">
    <link rel="stylesheet" href="css/TopNav.css">
    <script src="js/Dashboard.js" async defer></script>
    
</head>
        <style>
        #sidebar ul li.activeprofile a {
            color: var(--accent-clr);
            background-color: var(--hover-clr);
        }
        </style>
<body>
    <main>
        <section>
            <div id="Nav-container">
                <h1>BookSync Client & Receipts Management System</h1>
            </div>
        </section>

        <div class="container">
            <h3>Profile Details:</h3><br>
            <img class="Profileimg" src="https://jcrms.ct.ws/img/PROFILE.png" alt="Profile Image">
            <p>First Name: <?php echo htmlspecialchars($_SESSION['first_name']); ?></p>
            <p>User ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
        </div>
        
        <div class="container">
            <h3>Log In History:</h3>
            <br>
            <p class="lastlogin">Last login: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </main>
</body>
</html>
