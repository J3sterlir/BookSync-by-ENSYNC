<?php
session_start();
include("database.php");
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || !isset($_SESSION['role']) || $_SESSION['logged_in'] !== true) {
    session_unset();
    session_destroy();
    header("Location: Login.php?error=session_invalid");
    exit();
}

$success = false;
$error = "";
$receipts = [];

$successMessage = "";
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'add') {
        $successMessage = "Receipt successfully added.";
    } elseif ($_GET['success'] === 'edit') {
        $successMessage = "Receipt successfully edited.";
    } elseif ($_GET['success'] === 'delete') {
        $successMessage = "Receipt successfully deleted.";
    }
}

// Determine active tab for UI tab selection and error display
$active_tab = isset($_GET['active_tab']) ? $_GET['active_tab'] : 'input-details';

// Helper function to check client existence
function clientExists($conn, $client_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND type_id = 3"); //count how many users matches the given id and type of id or (3 being a client user)
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    $stmt->bind_param("s", $client_id);
    $stmt->execute();
    $stmt->bind_result($exists);
    $stmt->fetch();
    $stmt->close();

    error_log("Checking client ID: " . $client_id . " - Exists: " . $exists);

    return $exists > 0;
}

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['generate_summary'])) {
    $client_id = trim($_GET['client_id'] ?? '');
    if (!$client_id || !clientExists($conn, $client_id)) {
        $error = "User ID is not from a client or user does not exist.";
    }
    $active_tab = 'generate-summary'; // Show summary tab on generation
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Reset error
        $error = "";
        if ($_POST['action'] === 'add') {
            $client_id = trim($_POST['client_id'] ?? '');
            if (!$client_id || !clientExists($conn, $client_id)) {
                $error = "User ID is not from a client or user does not exist.";
            } else {
                $id = trim($_POST['id'] ?? '');
                $supplier = trim($_POST['supplier'] ?? '');
                $receipt_date = trim($_POST['receipt_date'] ?? '');
                $total = isset($_POST['total']) ? floatval($_POST['total']) : 0;

                if ($client_id && $id && $supplier && $receipt_date && $total > 0) {
                    //inserting receipts to receipts table with parameters such as id... values are given by the employee
                    $stmt = $conn->prepare("INSERT INTO receipts (id, client_id, supplier, receipt_date, total) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssd", $id, $client_id, $supplier, $receipt_date, $total);

                    if ($stmt->execute()) {
                        $stmt->close();
                        header("Location: receipt_info.php?client_id=" . urlencode($client_id) . "&active_tab=receipt-history&success=add&view_history=1");
                        exit();
                    } else {
                        $error = "Database error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Please fill in all fields and enter a valid amount.";
                }
            }
            // Set active tab so that error is shown in input tab
            $active_tab = 'input-details';
        } elseif ($_POST['action'] === 'edit') {
            // existing edit logic unchanged, but add setting active_tab similarly
            $edit_id = trim($_POST['edit_id'] ?? '');
            $edit_supplier = isset($_POST['edit_supplier']) ? trim($_POST['edit_supplier']) : '';
            $edit_receipt_date = isset($_POST['edit_receipt_date']) ? trim($_POST['edit_receipt_date']) : '';
            $edit_total = isset($_POST['edit_total']) ? floatval($_POST['edit_total']) : 0;

            if ($edit_id && $edit_supplier && $edit_receipt_date && $edit_total > 0) {
                $stmtClient = $conn->prepare("SELECT client_id FROM receipts WHERE id = ?");
                $stmtClient->bind_param("s", $edit_id);
                $stmtClient->execute();
                $resultClient = $stmtClient->get_result();
                $client_id_row = $resultClient->fetch_assoc();
                $stmtClient->close();
                $client_id = $client_id_row['client_id'] ?? '';

                if (clientExists($conn, $client_id)) {
                    //Update receipts and set the following parameters supplier,receipt_date.....
                    $stmt = $conn->prepare("UPDATE receipts SET supplier = ?, receipt_date = ?, total = ? WHERE id = ?");
                    $stmt->bind_param("ssds", $edit_supplier, $edit_receipt_date, $edit_total, $edit_id);

                    if ($stmt->execute()) {
                        $stmt->close();
                        header("Location: receipt_info.php?client_id=" . urlencode($client_id) . "&active_tab=receipt-history&success=edit&view_history=1");
                        exit();
                    } else {
                        $error = "Database error (update): " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "User ID is not from a client or user does not exist.";
                }
            } else {
                $error = "Please fill in all fields when editing and enter a valid amount.";
            }
            $active_tab = 'receipt-history';
        } elseif ($_POST['action'] === 'delete') {
            $delete_id = trim($_POST['delete_id'] ?? '');
            if ($delete_id) {
                //Selecting client id from receipts where id is given by the employee
                $stmtClient = $conn->prepare("SELECT client_id FROM receipts WHERE id = ?");
                $stmtClient->bind_param("s", $delete_id);
                $stmtClient->execute();
                $resultClient = $stmtClient->get_result();
                $client_id_row = $resultClient->fetch_assoc();
                $stmtClient->close();
                $client_id = $client_id_row['client_id'] ?? '';

                if (clientExists($conn, $client_id)) {
                    //delete the receipts referencing the given id by the employee
                    $stmt = $conn->prepare("DELETE FROM receipts WHERE id = ?");
                    $stmt->bind_param("s", $delete_id);

                    if ($stmt->execute()) {
                        $stmt->close();
                        header("Location: receipt_info.php?client_id=" . urlencode($client_id) . "&active_tab=receipt-history&success=delete&view_history=1");
                        exit();
                    } else {
                        $error = "Database error (delete): " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "User ID is not from a client or user does not exist.";
                }
            } else {
                $error = "Invalid receipt ID for deletion.";
            }
            $active_tab = 'receipt-history';
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['view_history'])) {
    $client_id = trim($_GET['client_id'] ?? '');
    if ($client_id) {
        if (clientExists($conn, $client_id)) {
            //select id... from receipts table condition where client is given by the employee
            $stmt = $conn->prepare("SELECT id, supplier, receipt_date, total, client_id FROM receipts WHERE client_id = ? ORDER BY receipt_date");
            $stmt->bind_param("s", $client_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $receipts = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $error = "User ID is not from a client or user does not exist.";
        }
    } else {
        $error = "Please provide a valid client ID.";
    }
    $active_tab = 'receipt-history';
}

$summary_receipts = [];
$total_receipts = 0;
$total_amount = 0.00;

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['generate_summary']) && !$error) {
    $client_id = trim($_GET['client_id'] ?? '');
    $summary_type = $_GET['summary_type'] ?? '';
    $year = $_GET['year'] ?? '';
    $quarter = $_GET['quarter'] ?? '';
    $month = $_GET['month'] ?? '';
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';

    if ($client_id && $summary_type) {
        if ($summary_type === 'annual' && $year) {
            $stmt = $conn->prepare("SELECT id, supplier, receipt_date, total FROM receipts WHERE client_id = ? AND YEAR(receipt_date) = ?");
            $stmt->bind_param("ss", $client_id, $year);
        } elseif ($summary_type === 'quarterly' && $year && $quarter) {
            $quarter_months = [
                1 => ['start' => '01', 'end' => '03'],
                2 => ['start' => '04', 'end' => '06'],
                3 => ['start' => '07', 'end' => '09'],
                4 => ['start' => '10', 'end' => '12'],
            ];
            $start_month = $quarter_months[$quarter]['start'];
            $end_month = $quarter_months[$quarter]['end'];
            $start_date = "$year-$start_month-01";
            $end_date = date("Y-m-t", strtotime("$year-$end_month-01"));
            $stmt = $conn->prepare("SELECT id, supplier, receipt_date, total FROM receipts WHERE client_id = ? AND receipt_date BETWEEN ? AND ?");
            $stmt->bind_param("sss", $client_id, $start_date, $end_date);
        } elseif ($summary_type === 'monthly' && $year && $month) {
            $start_date = "$year-$month-01";
            $end_date = date("Y-m-t", strtotime($start_date));
            $stmt = $conn->prepare("SELECT id, supplier, receipt_date, total FROM receipts WHERE client_id = ? AND receipt_date BETWEEN ? AND ?");
            $stmt->bind_param("sss", $client_id, $start_date, $end_date);
        } elseif ($summary_type === 'custom' && $from_date && $to_date) {
            $stmt = $conn->prepare("SELECT id, supplier, receipt_date, total FROM receipts WHERE client_id = ? AND receipt_date BETWEEN ? AND ?");
            $stmt->bind_param("sss", $client_id, $from_date, $to_date);
        } else {
            $stmt = null;
        }

        if (isset($stmt) && $stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $summary_receipts = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $total_receipts = count($summary_receipts);
            $total_amount = 0.00;
            foreach ($summary_receipts as $row) {
                $total_amount += floatval($row['total']);
            }
        }
    }
}

include('Component/nav-head.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Input Receipt Information</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/Dashboard.css">
    <link rel="stylesheet" href="css/TopNav.css">
    <link rel="stylesheet" href="css/receipt_info.css">
    <script src="js/Dashboard.js"></script>
    <script src="js/receipt_info.js"></script>
</head>
    <style>
        #sidebar ul li.activereceipts a {
            color: var(--accent-clr);
            background-color: var(--hover-clr);
        }
    </style>
<body>
    <main>
        <section class="section-1">
            <div id="Nav-container">
                <h1>JMCYK Client & Receipts Management System</h1>
            </div>
        </section>

        <h1>Receipt Information</h1>
        <p class type="sub-title">Manage receipts here!</p><br>

        <div class="tabs" role="tablist" aria-label="Receipt Tabs">
            <span class="tab-link<?php if ($active_tab === 'input-details') echo ' active'; ?>" data-tab="input-details" role="tab" tabindex="0" aria-selected="<?php echo ($active_tab === 'input-details') ? 'true' : 'false'; ?>">Input Receipt Details</span>
            <span class="tab-link<?php if ($active_tab === 'generate-summary') echo ' active'; ?>" data-tab="generate-summary" role="tab" tabindex="0" aria-selected="<?php echo ($active_tab === 'generate-summary') ? 'true' : 'false'; ?>">Generate Receipt Summary</span>
            <span class="tab-link<?php if ($active_tab === 'receipt-history') echo ' active'; ?>" data-tab="receipt-history" role="tab" tabindex="0" aria-selected="<?php echo ($active_tab === 'receipt-history') ? 'true' : 'false'; ?>">Receipt History</span>
        </div>

        <div class="tab-content<?php if ($active_tab === 'input-details') echo ' active'; ?>" id="input-details" role="tabpanel" tabindex="0">
            <form action="receipt_info.php" method="POST">
                
                <label>Store Receipts</label>
                <p class="desc">Input receipt details here!</p><br>

                <?php if ($successMessage && $active_tab === 'input-details'): ?>
                    <div style="color: green; font-weight: bold; margin-bottom: 1em;">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error && $active_tab === 'input-details'): ?>
                    <div style="color: red; margin-bottom: 1em;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
            
                <input type="hidden" name="action" value="add" />

                <div class="input-field">
                    <label>Client ID</label>
                    <input type="text" name="client_id" placeholder="Enter client id" value="<?php echo isset($_POST['client_id']) ? htmlspecialchars($_POST['client_id']) : ''; ?>" required>
                </div><br>

                <div class="input-field">
                    <label>Reference Number</label>
                    <input type="text" name="id" placeholder="Enter PK reference number" value="<?php echo isset($_POST['id']) ? htmlspecialchars($_POST['id']) : ''; ?>" required>
                </div><br>

                <div class="input-field">
                    <label>Supplier</label>
                    <input type="text" name="supplier" placeholder="Name of Supplier" value="<?php echo isset($_POST['supplier']) ? htmlspecialchars($_POST['supplier']) : ''; ?>" required>
                </div><br>

                <div class="input-field">
                    <label>Date</label>
                    <input type="date" name="receipt_date" value="<?php echo isset($_POST['receipt_date']) ? htmlspecialchars($_POST['receipt_date']) : ''; ?>" required>
                </div><br>

                <div class="input-field">
                    <label>Total Amount</label>
                    <input type="number" name="total" step="0.01" placeholder="Enter total amount" value="<?php echo isset($_POST['total']) ? htmlspecialchars($_POST['total']) : ''; ?>" required>
                </div><br>

                <input type="submit" name="input-details" value="Add Receipt" />
            </form>
        </div>

        <div class="tab-content<?php if (isset($_GET['active_tab']) && $_GET['active_tab'] === 'generate-summary') echo ' active'; ?>" id="generate-summary" role="tabpanel" tabindex="0">
            <form action="receipt_info.php" method="GET" id="summary-form">
                <label>Receipt Summary Generator</label>
                <p class="desc">Generate a summary using chosen timestamps here!</p><br>

                <?php if ($successMessage && isset($_GET['active_tab']) && $_GET['active_tab'] === 'generate-summary'): ?>
                    <div style="color: green; font-weight: bold; margin-bottom: 1em;">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error && isset($_GET['active_tab']) && $_GET['active_tab'] === 'generate-summary'): ?>
                    <div style="color: red; margin-bottom: 1em;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="active_tab" value="generate-summary">
                <label>Client ID</label>
                <div class="input-field">
                    <input type="text" name="client_id" placeholder="Enter client id to generate summary from" value="<?php echo isset($_GET['client_id']) ? htmlspecialchars($_GET['client_id']) : ''; ?>" required>
                </div><br>

                <label>Choose Frequency</label>
                <select name="summary_type" id="summary-type" onchange="toggleSummaryOptions()" required>
                    <option value="">Select Summary Type</option>
                    <option value="annual" <?php if(isset($_GET['summary_type']) && $_GET['summary_type']=='annual') echo 'selected'; ?>>Annual</option>
                    <option value="quarterly" <?php if(isset($_GET['summary_type']) && $_GET['summary_type']=='quarterly') echo 'selected'; ?>>Quarterly</option>
                    <option value="monthly" <?php if(isset($_GET['summary_type']) && $_GET['summary_type']=='monthly') echo 'selected'; ?>>Monthly</option>
                    <option value="custom" <?php if(isset($_GET['summary_type']) && $_GET['summary_type']=='custom') echo 'selected'; ?>>Custom Range</option>
                </select>

                <div id="annual-options" style="display:none;">
                    <label for="annual_year">Select Year:</label>
                    <select id="annual_year" name="year">
                        <?php
                        $currentYear = date("Y");
                        for ($year = 2020; $year <= $currentYear; $year++) {
                            echo "<option value=\"$year\"";
                            if(isset($_GET['year']) && $_GET['year']==$year && $_GET['summary_type']=='annual') echo ' selected';
                            echo ">$year</option>";
                        }
                        ?>
                    </select>
                </div>

                <div id="quarterly-options" style="display:none;">
                    <label for="quarterly_year">Select Year:</label>
                    <select id="quarterly_year" name="year">
                        <?php
                        for ($year = 2020; $year <= $currentYear; $year++) {
                            echo "<option value=\"$year\"";
                            if(isset($_GET['year']) && $_GET['year']==$year && $_GET['summary_type']=='quarterly') echo ' selected';
                            echo ">$year</option>";
                        }
                        ?>
                    </select>
                    <label for="quarter">Quarter:</label>
                    <select id="quarter" name="quarter">
                        <option value="1" <?php if(isset($_GET['quarter']) && $_GET['quarter']=='1') echo 'selected'; ?>>Q1 (Jan-Mar)</option>
                        <option value="2" <?php if(isset($_GET['quarter']) && $_GET['quarter']=='2') echo 'selected'; ?>>Q2 (Apr-Jun)</option>
                        <option value="3" <?php if(isset($_GET['quarter']) && $_GET['quarter']=='3') echo 'selected'; ?>>Q3 (Jul-Sep)</option>
                        <option value="4" <?php if(isset($_GET['quarter']) && $_GET['quarter']=='4') echo 'selected'; ?>>Q4 (Oct-Dec)</option>
                    </select>
                </div>

                <div id="monthly-options" style="display:none;">
                    <label for="monthly_year">Select Year:</label>
                    <select id="monthly_year" name="year">
                        <?php
                        for ($year = 2020; $year <= $currentYear; $year++) {
                            echo "<option value=\"$year\"";
                            if(isset($_GET['year']) && $_GET['year']==$year && $_GET['summary_type']=='monthly') echo ' selected';
                            echo ">$year</option>";
                        }
                        ?>
                    </select>
                    <label for="month">Month:</label>
                    <select id="month" name="month">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $monthVal = str_pad($m, 2, "0", STR_PAD_LEFT);
                            $monthName = date("F", mktime(0, 0, 0, $m, 10));
                            echo "<option value=\"$monthVal\"";
                            if(isset($_GET['month']) && $_GET['month']==$monthVal) echo ' selected';
                            echo ">$monthName</option>";
                        }
                        ?>
                    </select>
                </div>

                <div id="custom-options" style="display:none;">
                    <label for="from_date">From:</label>
                    <input type="date" id="from_date" name="from_date" value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : ''; ?>">
                    <label for="to_date">To:</label>
                    <input type="date" id="to_date" name="to_date" value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : ''; ?>">
                </div>

                <input type="submit" name="generate_summary" value="Generate Summary"><br><br>
            </form>

            <?php if (isset($_GET['generate_summary']) && $client_id && $summary_type && !$error): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Total Number of Receipts</th>
                            <th>Total Amount of All Receipts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($total_receipts); ?></td>
                            <td><?php echo '₱' . htmlspecialchars(number_format($total_amount, 2)); ?></td>
                        </tr>
                    </tbody>
                </table>

                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt ID</th>
                            <th>Total Amount</th>
                            <th>Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($summary_receipts)): ?>
                            <?php foreach ($summary_receipts as $receipt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($receipt['receipt_date']); ?></td>
                                    <td><?php echo htmlspecialchars($receipt['id']); ?></td>
                                    <td><?php echo '₱' . htmlspecialchars(number_format($receipt['total'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($receipt['supplier']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>



        <div class="tab-content<?php if (isset($_GET['active_tab']) && $_GET['active_tab'] === 'receipt-history') echo ' active'; ?>" id="receipt-history" role="tabpanel" tabindex="0">
            <label>Receipt History</label>
            <p class="desc">View the history of added receipts here!</p><br>

            <?php if ($successMessage && isset($_GET['active_tab']) && $_GET['active_tab'] === 'receipt-history'): ?>
                <div style="color: green; font-weight: bold; margin-bottom: 1em;">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($error && isset($_GET['active_tab']) && $_GET['active_tab'] === 'receipt-history'): ?>
                <div style="color: red; margin-bottom: 1em;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="receipt_info.php" method="GET" style="margin-bottom: 1em;">
                <input type="hidden" name="active_tab" value="receipt-history">
                <label>Client ID</label>
                <input type="text" name="client_id" placeholder="Enter client ID to view history" value="<?php echo isset($_GET['client_id']) ? htmlspecialchars($_GET['client_id']) : ''; ?>" required>
                <button type="submit" name="view_history">
                    View Receipt History
                </button>
            </form>

            <?php if (!empty($receipts)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt ID</th>
                            <th>Total Amount</th>
                            <th>Supplier</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receipts as $receipt): ?>
                            <tr id="row-<?php echo htmlspecialchars($receipt['id']); ?>">
                                <td class="cell-date"><?php echo htmlspecialchars($receipt['receipt_date']); ?></td>
                                <td class="cell-id"><?php echo htmlspecialchars($receipt['id']); ?></td>
                                <td class="cell-total"><?php echo '₱' . htmlspecialchars(number_format($receipt['total'], 2)); ?></td>
                                <td class="cell-supplier"><?php echo htmlspecialchars($receipt['supplier']); ?></td>
                                <td class="cell-actions">
                                    <button class="edit" type="button" onclick="enableEdit('<?php echo htmlspecialchars($receipt['id']); ?>')">Edit</button>
                                    <form method="POST" class="inline-form" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this receipt?');">
                                        <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($receipt['id']); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php elseif (isset($_GET['view_history'])): ?>
                    <p class="desc">No receipts found for this user.</p>
            <?php endif; ?>
        </div>

    </main>
</body>
</html>

<?php $conn->close(); ?>