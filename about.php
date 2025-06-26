<?php
// Initialize session FIRST
session_start();

// Include database connection file (ensure it doesn't output anything)
include("database.php");
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

// Check if the connection was successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Validate session
if (!isset($_SESSION['user_id']) || 
    !isset($_SESSION['username']) || 
    !isset($_SESSION['logged_in']) || 
    !isset($_SESSION['role']) || 
    $_SESSION['logged_in'] !== true) {
    
    // Destroy invalid session
    session_unset();
    session_destroy();
    
    // Redirect to login and echo error
    header("Location: index.php?error=session_invalid");
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
    <title>Employee Home</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/Dashboard.css">
    <link rel="stylesheet" href="css/TopNav.css">
    <script src="js/Dashboard.js" async defer></script>

</head>
        <style>
        #sidebar ul li.activeabout a {
            color: var(--accent-clr);
            background-color: var(--hover-clr);
        }
        </style>
<body>
    <main>
        <section>
            <div id="Nav-container">
                <h1>JMCYK Client & Receipts Management System</h1>
            </div>
        </section>

        <div class="container">
            <h1>About</h1><br>
            <p>JMCYK Bookkeeping Services, a company that manages financial transactions of taxpayers. Located at 2/F McKay Building, Zone 5, Corner Panganiban Drive, Concepcion Pequeña, Naga City and is owned by Mrs. Maricel M. Brioso</p>
        </div>
    </main> 
</body>
</html>
