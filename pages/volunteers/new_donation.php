<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Location: /soemone/login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Input sanitization and validation
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
        $user_id = $_SESSION['user_id'];
        $status = 'Pending';

        if (!$amount || $amount <= 0) {
            throw new Exception("Please enter a valid amount greater than 0.");
        }

        if (!$payment_method) {
            throw new Exception("Please select a payment method.");
        }

        // Insert donation into the database
        $stmt = $conn->prepare("
            INSERT INTO donations (
                user_id, 
                amount, 
                payment_method, 
                status, 
                created_at, 
                updated_at
            ) VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        
        if ($stmt->execute([$user_id, $amount, $payment_method, $status])) {
            header('Location: /soemone/pages/volunteers/donations.php?success=1');
            exit();
        } else {
            throw new Exception("Failed to process donation. Please try again.");
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Donation - sparkservices</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
    function validateDonation() {
        const amountInput = document.getElementById('amount');
        const paymentMethodSelect = document.getElementById('payment_method');

        if (!amountInput.value || parseFloat(amountInput.value) <= 0) {
            alert('Please enter a valid amount greater than 0.');
            return false;
        }

        if (!paymentMethodSelect.value) {
            alert('Please select a payment method.');
            return false;
        }

        // If payment method is Scan QR, show modal instead of submitting form
        if (paymentMethodSelect.value === 'Scan QR') {
            document.getElementById('qrModal').classList.remove('hidden');
            return false; // prevent form submission for now
        }

        return true;
    }

    function submitAfterQR() {
        // Close the modal
        document.getElementById('qrModal').classList.add('hidden');
        // Submit the form
        document.querySelector('form').submit();
    }
</script>

</head>
<body class="bg-yellow-50">
    <nav class="bg-white shadow-lg border-b-2 border-yellow-700">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/soemone" class="text-xl font-bold text-yellow-700">
                            <i class="fas fa-hands-helping mr-2 text-yellow-600"></i>sparkservices
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/soemone/pages/volunteers/dashboard.php" class="border-transparent text-gray-700 hover:text-yellow-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-home mr-2"></i> Dashboard
                        </a>
                        <a href="/soemone/pages/volunteers/donations.php" class="border-yellow-500 text-yellow-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-donate mr-2"></i> Donations
                        </a>
                        <a href="/soemone/pages/volunteers/events.php" class="border-transparent text-gray-700 hover:text-yellow-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-calendar-alt mr-2"></i> Events
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-gray-700 mr-4"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <a href="/soemone/logout.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-700 hover:bg-yellow-800">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Make a New Donation</h2>
                    <p class="mt-1 text-sm text-gray-600">Please fill in the details below to make a new donation.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

<div id="qrModal" class="hidden fixed z-10 inset-0 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-96 text-center">
        <h3 class="text-lg font-bold mb-4">Scan to Donate</h3>
        <img src="/soemone/code.jpeg" alt="QR Code" class="mx-auto mb-4 w-48 h-48 object-contain border border-gray-300 rounded">
        <p class="text-sm text-gray-600 mb-4">Scan the QR code using your payment app to complete the donation.</p>
        <button onclick="submitAfterQR()" class="mt-2 px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
            I have scanned
        </button>
    </div>
</div>


                <form method="POST" class="space-y-6" onsubmit="return validateDonation();">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount ($) *</label>
                        <input type="number" step="0.01" min="0" name="amount" id="amount" required
                            class="mt-1 block w-full sm:text-sm border-2 border-gray-300 rounded-md h-10 px-3 focus:border-yellow-500 focus:shadow-lg outline-none transition duration-200"
                            placeholder="0.00">
                    </div>

                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method *</label>
                        <select name="payment_method" id="payment_method" required
                            class="shadow-sm focus:ring-yellow-500 focus:border-yellow-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            <option value="">Select a payment method</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="PayPal">PayPal</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Scan QR">Scan QR</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end space-x-4">
                        <a href="/soemone/pages/volunteers/donations.php"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-700 hover:bg-yellow-800">
                            <i class="fas fa-donate mr-2"></i> Make Donation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
