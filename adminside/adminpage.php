<?php
include("../database.php");
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || 
    !isset($_SESSION['username']) || 
    !isset($_SESSION['logged_in']) || 
    !isset($_SESSION['role']) ||  
    $_SESSION['logged_in'] !== true) {
    
    // Destroy invalid session
    session_unset();
    session_destroy();
    
    // Redirect to login with error message
    header("Location: ../index.php?error=session_invalid");
    exit();
}

// Handle logout
if (isset($_POST["logout"])) {
    // Logout logic
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

// Variables to handle messages and errors
$message = '';
$error = '';

// Get search term from GET for filtering users
$search = trim($_GET['search'] ?? '');

// Determine the action (create, update, delete)
$action = $_GET['action'] ?? '';

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "User  deleted successfully.";
    } else {
        $error = "Failed to delete user.";
    }
}

// Handle Create / Update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clean inputs
    $id = $_POST['id'] ?? null;
    $type_id = trim($_POST['type_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($type_id) || empty($name) || empty($email) || empty($username)) {
        $error = "Please fill in all required fields (Type ID, Name, Email, Username).";
    } else {
        if (empty($id)) {
            // Create
            $password = $_POST['password'] ?? '';
            if (empty($password)) {
                $error = "Password is required for new users.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (type_id, name, email, phone_number, username, password, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssi", $type_id, $name, $email, $phone_number, $username, $password_hash, $is_active);
                if ($stmt->execute()) {
                    $message = "User  created successfully.";
                } else {
                    $error = "Error creating user: " . $stmt->error;
                }
            }
        } else {
            // Update (do not update password here)
            $stmt = $conn->prepare("UPDATE users SET type_id = ?, name = ?, email = ?, phone_number = ?, username = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("issssii", $type_id, $name, $email, $phone_number, $username, $is_active, $id);
            if ($stmt->execute()) {
                $message = "User  updated successfully.";
            } else {
                $error = "Error updating user: " . $stmt->error;
            }
        }
    }
}

function getUserTypeName($type_id) {
    switch ($type_id) {
        case 2:
            return 'Employee';
        case 3:
            return 'Client';
        default:
            return 'Unknown';
    }
}

// Fetch user for edit if needed
$editUser  = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editUser  = $result->fetch_assoc();
}

// Fetch all users for list with exclusion of admin username and search filter
if ($search !== '') {
    $likeSearch = '%' . $search . '%';
    $stmt = $conn->prepare("SELECT * FROM users WHERE username != 'admin' AND (name LIKE ? OR email LIKE ? OR username LIKE ?) ORDER BY id DESC");
    $stmt->bind_param("sss", $likeSearch, $likeSearch, $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // No search - fetch all excluding admin username
    $stmt = $conn->prepare("SELECT * FROM users WHERE username != 'admin' ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
}
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Panel</title>
<link rel="stylesheet" href="css/Admin.css">

<script>
function validateForm() {
  let x = document.forms["searchfunciton"]["search"].value;
  if (x == "") {
    alert("Empty search");
    return false;
  }
}
</script>

</head>

<body>
    <header>
        <nav class="adminnav">
            <h1>JMCYK Admin Page</h1>
        </nav>
    </header>

    <div class="flexcontainer">
        <div class="header-contain">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
            <br>
            <p>Manage Your Users Here</p>
            <br>
            <form action="adminpage.php" method="post" id="logoutform" class="formbutton">
                <input type="submit" name="logout" value="   ⬅  Logout" id="logoutbtn">
            </form>
        </div>
    </div>


<div class="flexcontainer">
<div class="header-contain">
    <h1><?= $editUser  ? 'Edit User' : 'Create User' ?></h1>
    <br>
    <hr>
    <br>

    <?php if ($message): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="id" value="<?= $editUser ['id'] ?? '' ?>">
        <label for="type_id">User Type *</label>
        <select id="type_id" name="type_id" required>
            <option value="">-- Select User Type --</option>
            <option value="2" <?= (isset($editUser['type_id']) && $editUser['type_id'] == 2) ? 'selected' : '' ?>>Employee</option>
            <option value="3" <?= (isset($editUser['type_id']) && $editUser['type_id'] == 3) ? 'selected' : '' ?>>Client</option>
        </select>


        <label for="name">Name *</label>
        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($editUser ['name'] ?? '') ?>">

        <label for="email">Email *</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($editUser ['email'] ?? '') ?>">

        <label for="phone_number">Phone Number</label>
        <input type="text" id="phone_number" name="phone_number" value="<?= htmlspecialchars($editUser ['phone_number'] ?? '') ?>">

        <label for="username">Username *</label>
        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($editUser ['username'] ?? '') ?>">

        <?php if (!$editUser ): ?>
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required>
        <?php endif; ?>

        <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (isset($editUser ['is_active']) && $editUser ['is_active']) ? 'checked' : ''; ?>> Active</label>
        <button class="form-button" type="submit"><?= $editUser  ? 'Update User' : 'Create User' ?></button>
        <br><br>
        <hr>
        <br>
        <?php if ($editUser ): ?>
            <a class="button" href="?">Cancel</a>
        <?php endif; ?>
    </form>

    <h2>User List</h2>
    <br>

    <form name="searchfunciton" class="search-bar" onsubmit="return validateForm()" method="GET" action="adminpage.php">
        <input type="text" name="search" placeholder="Search by name, email, username..." value="<?= htmlspecialchars($search) ?>">
        <button class="searchbutton" type="submit">Search</button>
        <?php if ($search !== ''): ?>
            <a class="button clear-btn" href="?">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($users)): ?>
        <p>No users found.</p>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Type ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Username</th>
                <th>Active</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        </table>
    <?php else: ?>
        
    <div class="flexcontainer">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Type ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Username</th>
                <th>Active</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars(getUserTypeName($user['type_id'])) ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phone_number']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= $user['is_active'] ? 'Yes' : 'No' ?></td>
                <td class="actions">
                    <a class="button" href="?action=edit&amp;id=<?= $user['id'] ?>">Edit</a>
                    <a class="button" href="?action=delete&amp;id=<?= $user['id'] ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
</div>
</body>
</html>