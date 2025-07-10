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

$clients = [];
$clientQuery = "SELECT id, name FROM users WHERE type_id = 3"; // Assuming type_id = 3 is for clients
$clientResult = $conn->query($clientQuery);
if ($clientResult) {
    $clients = $clientResult->fetch_all(MYSQLI_ASSOC);
} else {
    $error = "Failed to retrieve clients: " . $conn->error;
}

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
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND type_id = 3");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    $stmt->bind_param("s", $client_id);
    $stmt->execute();
    $stmt->bind_result($exists);
    $stmt->fetch();
    $stmt->close();
    return $exists > 0;
}

// Handle GET and POST requests
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['generate_summary'])) {
    $client_id = trim($_GET['client_id'] ?? '');
    if (!$client_id || !clientExists($conn, $client_id)) {
        $error = "User  ID is not from a client or user does not exist.";
    }
    $active_tab = 'generate-summary'; // Show summary tab on generation
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $error = "";
    if (isset($_POST['action'])) {
        $client_id = trim($_POST['client_id'] ?? '');
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            if (!$client_id || !clientExists($conn, $client_id)) {
                $error = "User  ID is not from a client or user does not exist.";
            } else {
                $id = trim($_POST['id'] ?? '');
                $supplier = trim($_POST['supplier'] ?? '');
                $receipt_date = trim($_POST['receipt_date'] ?? '');
                $total = isset($_POST['total']) ? floatval($_POST['total']) : 0;

                if ($id && $supplier && $receipt_date && $total > 0) {
                    if ($_POST['action'] === 'add') {
                        $stmt = $conn->prepare("INSERT INTO receipts (id, client_id, supplier, receipt_date, total) VALUES (?, ?, ?, ?, ?)");
                    } else {
                        $stmt = $conn->prepare("UPDATE receipts SET supplier = ?, receipt_date = ?, total = ? WHERE id = ?");
                        $stmt->bind_param("ssds", $supplier, $receipt_date, $total, $id);
                    }
                    $stmt->bind_param("ssssd", $id, $client_id, $supplier, $receipt_date, $total);
                    if ($stmt->execute()) {
                        $stmt->close();
                        header("Location: receipt_info.php?client_id=" . urlencode($client_id) . "&active_tab=receipt-history&success=" . ($_POST['action'] === 'add' ? 'add' : 'edit') . "&view_history=1");
                        exit();
                    } else {
                        $error = "Database error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Please fill in all fields and enter a valid amount.";
                }
            }
            $active_tab = 'input-details';
        } elseif ($_POST['action'] === 'delete') {
            $delete_id = trim($_POST['delete_id'] ?? '');
            if ($delete_id) {
                $stmtClient = $conn->prepare("SELECT client_id FROM receipts WHERE id = ?");
                $stmtClient->bind_param("s", $delete_id);
                $stmtClient->execute();
                $resultClient = $stmtClient->get_result();
                $client_id_row = $resultClient->fetch_assoc();
                $stmtClient->close();
                $client_id = $client_id_row['client_id'] ?? '';

                if (clientExists($conn, $client_id)) {
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
                    $error = "User  ID is not from a client or user does not exist.";
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
            $stmt = $conn->prepare("SELECT id, supplier, receipt_date, total FROM receipts WHERE client_id = ? ORDER BY receipt_date");
            $stmt->bind_param("s", $client_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $receipts = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $error = "User  ID is not from a client or user does not exist.";
        }
    } else {
        $error = "Please provide a valid client ID.";
    }
    $active_tab = 'receipt-history';
}

include('Component/nav-head.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Receipt Management</title>
    <link rel="stylesheet" href="css/Dashboard.css">
    <link rel="stylesheet" href="css/TopNav.css">
    <link rel="stylesheet" href="css/receipt_info.css">
    <script src="js/Dashboard.js"></script>
    <script src="js/receipt_info.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            color: black;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: 1.5rem;
        }
        /* Additional styling for elements */
        .receipt-item {
            transition: background-color 0.2s;
        }
        .receipt-item:hover {
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="bg-gray-100">
    <main>
        <section class="section-1">
            <div id="Nav-container">
                <h1>BookSync Client & Receipts Management System</h1>
            </div>
        </section>

        <!-- Client List Section -->
        <section class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Client List</h2>
            
            <?php if (!empty($clients)): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($client['name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($client['id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex space-x-2">
                                        <button onclick="setCurrentClient('<?php echo $client['id']; ?>');openModal('inputModal')" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                                            Input Receipt
                                        </button>
                                        <button onclick="setCurrentClient('<?php echo $client['id']; ?>');openModal('summaryModal')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                            Generate Summary
                                        </button>
                                        <button onclick="setCurrentClient('<?php echo $client['id']; ?>');openModal('historyModal')" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition">
                                            View History
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500">No clients found.</p>
            <?php endif; ?>
        </section>

        <!-- Input Receipt Modal -->
        <div id="inputModal" class="modal">
            <div class="modal-content relative">
                <span class="close-btn" onclick="closeModal('inputModal')">&times;</span>
                <h2 class="text-2xl font-bold mb-4 text-gray-800">Input New Receipt</h2>
                
                <form action="receipt_info.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="client_id" id="modal_client_id">
                    
                    <div>
                        <label for="id" class="block text-sm font-medium text-gray-700">Reference Number</label>
                        <input type="text" name="id" id="id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div>
                        <label for="supplier" class="block text-sm font-medium text-gray-700">Supplier</label>
                        <input type="text" name="supplier" id="supplier" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div>
                        <label for="receipt_date" class="block text-sm font-medium text-gray-700">Date</label>
                        <input type="date" name="receipt_date" id="receipt_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div>
                        <label for="total" class="block text-sm font-medium text-gray-700">Total Amount</label>
                        <input type="number" name="total" id="total" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">
                            Add Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Generate Summary Modal -->
        <div id="summaryModal" class="modal">
            <div class="modal-content relative">
                <span class="close-btn" onclick="closeModal('summaryModal')">&times;</span>
                <h2 class="text-2xl font-bold mb-4 text-gray-800">Generate Receipt Summary</h2>
                
                <form action="receipt_info.php" method="GET" class="space-y-4">
                    <input type="hidden" name="generate_summary" value="1">
                    <input type="hidden" name="client_id" id="summary_client_id">
                    
                    <div>
                        <label for="summary_type" class="block text-sm font-medium text-gray-700">Summary Type</label>
                        <select name="summary_type" id="summary_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="annual">Annual</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="monthly">Monthly</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <div id="annualOptions" class="hidden">
                        <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                        <input type="text" name="year" id="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div id="quarterlyOptions" class="hidden">
                        <label for="quarter" class="block text-sm font-medium text-gray-700">Quarter</label>
                        <select name="quarter" id="quarter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="1">Q1 (Jan-Mar)</option>
                            <option value="2">Q2 (Apr-Jun)</option>
                            <option value="3">Q3 (Jul-Sep)</option>
                            <option value="4">Q4 (Oct-Dec)</option>
                        </select>
                    </div>
                    
                    <div id="customOptions" class="hidden space-y-4">
                        <div>
                            <label for="from_date" class="block text-sm font-medium text-gray-700">From Date</label>
                            <input type="date" name="from_date" id="from_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label for="to_date" class="block text-sm font-medium text-gray-700">To Date</label>
                            <input type="date" name="to_date" id="to_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                            Generate Summary
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- View History Modal -->
        <div id="historyModal" class="modal">
            <div class="modal-content relative">
                <span class="close-btn" onclick="closeModal('historyModal')">&times;</span>
                <h2 class="text-2xl font-bold mb-4 text-gray-800">Receipt History</h2>
                
                <form action="receipt_info.php" method="GET" class="mb-6">
                    <div class="flex gap-2">
                        <input type="hidden" name="client_id" id="history_client_id">
                        <button type="submit" name="view_history" class="bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700">
                            View History
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($receipts)): ?>
                    <div class="space-y-4">
                        <?php foreach ($receipts as $receipt): ?>
                            <div class="receipt-item p-4 border rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium">Receipt #<?php echo htmlspecialchars($receipt['id']); ?></h3>
                                        <p class="text-sm text-gray-600">Date: <?php echo htmlspecialchars($receipt['receipt_date']); ?></p>
                                        <p class="text-sm text-gray-600">Supplier: <?php echo htmlspecialchars($receipt['supplier']); ?></p>
                                        <p class="text-sm text-gray-600">Amount: ₱<?php echo number_format($receipt['total'], 2); ?></p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button class="text-sm bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Edit</button>
                                        <button class="text-sm bg-red-100 text-red-800 px-2 py-1 rounded">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No receipts found for this client.<?php endif; ?>
            </div>
        </div>

        <script>
            // Track current client
            let currentClientId = null;

            // Set the current client ID and update hidden input fields
            function setCurrentClient(clientId) {
                currentClientId = clientId;
                document.getElementById('modal_client_id').value = clientId;
                document.getElementById('summary_client_id').value = clientId;
                document.getElementById('history_client_id').value = clientId;
            }

            // Function to open the specified modal
            function openModal(modalId) {
                document.getElementById(modalId).style.display = 'flex';
            }

            // Function to close the specified modal
            function closeModal(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }

            // Close modal when clicking outside the modal content
            window.onclick = function(event) {
                const modals = ['inputModal', 'summaryModal', 'historyModal'];
                modals.forEach(modalId => {
                    if (event.target.id === modalId) {
                        closeModal(modalId);
                    }
                });
            }

            // Summary type selector logic
            document.getElementById('summary_type').addEventListener('change', function() {
                const val = this.value;
                document.getElementById('annualOptions').classList.add('hidden');
                document.getElementById('quarterlyOptions').classList.add('hidden');
                document.getElementById('customOptions').classList.add('hidden');

                if (val === 'annual') {
                    document.getElementById('annualOptions').classList.remove('hidden');
                } else if (val === 'quarterly') {
                    document.getElementById('quarterlyOptions').classList.remove('hidden');
                } else if (val === 'custom') {
                    document.getElementById('customOptions').classList.remove('hidden');
                }
            });

            // Trigger change event on load to set initial visibility
            document.getElementById('summary_type').dispatchEvent(new Event('change'));
        </script>
    </main>
</body>
</html>
