<?php
include("../database.php");
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

session_start();

// Log checker
if (!isset($_SESSION['user_id']) || 
    !isset($_SESSION['username']) || 
    !isset($_SESSION['logged_in']) || 
    !isset($_SESSION['role']) ||  
    $_SESSION['logged_in'] !== true) {
    
    // Destroy if log invalid
    session_unset();
    session_destroy();
    
    // Login err handling
    header("Location: ../index.php?error=session_invalid");
    exit();
}

// Getting user id
$user_id = $_SESSION['user_id'];

// fetching rec for specific user
$receipts_query = "SELECT * FROM receipts WHERE client_id = '$user_id' ORDER BY receipt_date DESC";
$receipts_result = mysqli_query($conn, $receipts_query);

// Logout func
if (isset($_POST["logout"])) {
    // Logout func proper
    session_regenerate_id(true);
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: ../index.php");
    exit();
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Client Page</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="../clientside/css/Client.css">
    </head>
    <body>

        <header>
            <nav class="clientnnav">
                <h1>JMCYK Client Page</h1>
            </nav>
        </header>

        <div class="flexcontainer">
            <div class="header-contain">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
                <br>
                <p>View Your Receipts Here</p>
                <br>
                <form action="clientview.php" method="post" id="logoutform" class="formbutton">
                    <input type="submit" name="logout" value="   ⬅  Logout" id="logoutbtn">
                </form>
            </div>
        </div>

        <?php if(mysqli_num_rows($receipts_result) > 0): ?>
        <div class="flexcontainer">
            <div class="header-contain">
                <table style="color: black">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt ID</th>
                            <th>Total Amount</th>
                            <th>Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($receipt = mysqli_fetch_assoc($receipts_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($receipt['receipt_date']); ?></td>
                            <td><?php echo htmlspecialchars($receipt['id']); ?></td>
                            <td><?php echo '₱' . number_format($receipt['total'], 2); ?></td>
                            <td><?php echo htmlspecialchars($receipt['supplier'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-receipts">
                    <p>No receipts found for your account.</p>
                </div>
                <?php endif; ?>
                </div>
        </div>
    </body>
</html>

<?php
// Close database connection
mysqli_close($conn);
?>