<?php
include("database.php");
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

// Variables to handle messages and errors
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clean inputs
    $type_id = trim($_POST['type_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($type_id) || empty($name) || empty($email) || empty($username) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            // Create new user (automatically active - is_active = 1)
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (type_id, name, email, phone_number, username, password, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("isssss", $type_id, $name, $email, $phone_number, $username, $password_hash);
            
            if ($stmt->execute()) {
                $message = "Registration successful! You can now login.";
                // Clear form
                $type_id = $name = $email = $phone_number = $username = '';
            } else {
                $error = "Error during registration: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Registration</title>
<link rel="stylesheet" href="./adminside/css/Admin.css">
</head>

<body>
    <header>
        <nav class="adminnav">
            <h1>BOOKSYNC Registration</h1>
        </nav>
    </header>

    <div class="flexcontainer">
        <div class="header-contain">
            <h1>Create Your Account</h1>
            <br>
            <p>Join our platform today</p>
            <br>
            <a href="Login.php" class="button" style="text-decoration: none">⬅ Back to Login</a>
        </div>
    </div>

    <div class="flexcontainer">
        <div class="header-contain">
            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <label for="type_id">Account Type *</label>
                <select id="type_id" name="type_id" required>
                    <option value="">-- Select Account Type --</option>
                    <option value="2" <?= (isset($type_id) && $type_id == 2) ? 'selected' : '' ?>>Employee</option>
                    <option value="3" <?= (isset($type_id) && $type_id == 3) ? 'selected' : '' ?>>Client</option>
                </select>

                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($name ?? '') ?>">

                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>">

                <label for="phone_number">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" value="<?= htmlspecialchars($phone_number ?? '') ?>">

                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username ?? '') ?>">

                <label for="password">Password * (min 8 characters)</label>
                <input type="password" id="password" name="password" required minlength="8">

                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                <br>
                <button class="form-button" type="submit">Register</button>
            </form>

            <br>
            <hr>
            <br>
            <p>Already have an account? <a href="Login.php"><b>Login here</b></a></p>
        </div>
    </div>
</body>
</html>