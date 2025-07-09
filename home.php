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
?>

<?php
include('Component/nav-head.php');
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Employee Home</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="css/Dashboard.css">
        <link rel="stylesheet" href="css/TopNav.css">
        <script src="js/Dashboard.js" async defer></script>
        
    </head>
        <style>
        #sidebar ul li.activehome a {
            color: var(--accent-clr);
            background-color: var(--hover-clr);
        }
    </style>
    <body>
        
    </body>

    <main>
            <section>
                <div id="Nav-container">
                    <h1>BookSync Client & Receipts Management System</h1>
                </div>
            </section>

            <div class="container">
            <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
            <br><hr>
                <br>
                <h2>Available Services</h2><br>
                <ul>
                    <li>Receipt Summary Generator</li>
                    <li>View Client Information</li>
                    <li>View Added Receipts History</li>
                </ul>
            </div>
    </main>
</html>


