<?php
session_start();

include("database.php");
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

// Check if the connection was successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check session validity
if (!isset($_SESSION['user_id']) || 
    !isset($_SESSION['username']) || 
    !isset($_SESSION['logged_in']) || 
    !isset($_SESSION['role']) || 
    $_SESSION['logged_in'] !== true) {
    
    session_unset();
    session_destroy();
    header("Location: index.php?error=session_invalid");
    exit();
}

$user_id = $_SESSION['user_id'];

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $new_username = trim($_POST['username'] ?? '');
    $new_name = trim($_POST['name'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($new_username) || empty($new_name)) {
        $error_msg = "Username and Name cannot be empty.";
    } else {
        // Fetch current password hash
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $hashed_password);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        $password_change = false;
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            $password_change = true;

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_msg = "To change password, fill in all password fields.";
            } elseif (!password_verify($current_password, $hashed_password)) {
                $error_msg = "Current password is incorrect.";
            } elseif ($new_password !== $confirm_password) {
                $error_msg = "New password and confirmation do not match.";
            } elseif (strlen($new_password) < 6) {
                $error_msg = "New password must be at least 6 characters.";
            }
        }

        if (empty($error_msg)) {
            // Update username and name
            $sql_update = "UPDATE users SET username = ?, name = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "ssi", $new_username, $new_name, $user_id);
            $update_success = mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);

            if ($update_success) {
                if ($password_change) {
                    $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_pass = "UPDATE users SET password = ? WHERE id = ?";
                    $stmt_pass = mysqli_prepare($conn, $sql_pass);
                    mysqli_stmt_bind_param($stmt_pass, "si", $new_password_hashed, $user_id);
                    $pass_success = mysqli_stmt_execute($stmt_pass);
                    mysqli_stmt_close($stmt_pass);

                    if ($pass_success) {
                        $success_msg = "Profile and password updated successfully.";
                    } else {
                        $error_msg = "Failed to update password.";
                    }
                } else {
                    $success_msg = "Profile updated successfully.";
                }

                $_SESSION['username'] = $new_username; // Update session username
            } else {
                $error_msg = "Failed to update profile.";
            }
        }
    }
}

// Fetch current user info
$sql = "SELECT username, name FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $current_username, $current_name);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

include('Component/nav-head.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Settings - JMCYK Client Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="css/Dashboard.css" />
    <link rel="stylesheet" href="css/TopNav.css" />
    <link rel="stylesheet" href="css/settings.css" />
    <script src="js/Dashboard.js" async defer></script>
    <script src="js/settings.js"></script>
</head>
        <style>
        #sidebar ul li.activesettings a {
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

            <h1>Settings</h1>
            <br>
            <?php if ($success_msg): ?>
                <div class="feedback success"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="feedback error"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <div class="tabs" role="tablist" aria-label="Settings Tabs">
                <span class="tab-link active" data-tab="account" role="tab" tabindex="0" aria-selected="true">Account Settings</span>
                <span class="tab-link" data-tab="terms" role="tab" tabindex="0" aria-selected="false">Terms & Conditions</span>
                <span class="tab-link" data-tab="info" role="tab" tabindex="0" aria-selected="false">Help & FAQs</span>
            </div>
            <div class="tab-content active" id="account" role="tabpanel">
                <form class="settings-form" id="settingsForm" method="post" action="settings.php" novalidate>
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($current_username); ?>" />

                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($current_name); ?>" />

                    <hr style="margin-top: 30px; margin-bottom: 30px;" />

                    <h3>Change Password</h3>
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Current Password" />

                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="New Password" />

                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" />

                    <input type="submit" value="Save Changes" />
                </form>
            </div>

            <div class="tab-content" id="terms" role="tabpanel" hidden style="padding: 30px;">
                <h2>Terms & Conditions</h2>
                <br>
                <p>
                    Welcome to JMCYK Client Management System. By using our services, you agree to comply with the following terms and conditions:
                    <br>
                    <br>
                    <ul>
                        <li>Use the system responsibly and ethically.</li>
                        <li>Do not share your login credentials.</li>
                        <li>Respect confidentiality of client information.</li>
                        <li>Follow all applicable laws and regulations.</li>
                        <li>We reserve the right to modify these terms at any time.</li>
                    </ul>
                </p>
            </div>

            <div class="tab-content" id="info" role="tabpanel" hidden style="padding: 30px;">
                <h2>Help and FAQ's</h2>
                <br>
                <p>
                    JMCYK Client Management System v1.0<br />
                    Developed by Lirag-Madera.<br />
                    For support, contact: Maricel M. Brioso<br /><br />
                    <strong>Frequently Asked Questions:</strong>
                    <br>
                    <ul>
                        <strong>How to create changes in my account?</strong> Refer to the Account Settings tab as it contains fields to edit on.<br />
                        <strong>Who do I contact for assistance?</strong> Reach out to Maricel M. Brioso for any questions.<br />
                        <strong>Where can I find terms and conditions?</strong> Please refer to the 'Terms & Conditions' tab.
                    </ul>
                </p>
            </div>
    </main>
</body>
</html>
