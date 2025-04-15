<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireLogin();

$database = new Database();
$conn = $database->getConnection();

$success_message = '';
$error_message = '';

// Remove past events
try {
    $stmt = $conn->prepare("DELETE FROM events WHERE date_time < NOW()");
    $stmt->execute();
} catch (PDOException $e) {
    $error_message = 'Error removing past events: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $required_fields = ['title', 'description', 'event_date', 'start_time', 'location', 'max_volunteers'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        if (!is_numeric($_POST['max_volunteers']) || $_POST['max_volunteers'] < 1) {
            throw new Exception("Maximum volunteers must be a positive number.");
        }

        $date_time = $_POST['event_date'] . ' ' . $_POST['start_time'];

        // Check if date_time is in the past
        if (strtotime($date_time) < time()) {
            throw new Exception("You cannot add an event in the past.");
        }

        $stmt = $conn->prepare("
            INSERT INTO events (title, description, date_time, location, max_volunteers, status, created_by) 
            VALUES (:title, :description, :date_time, :location, :max_volunteers, :status, :created_by)
        ");

        $stmt->execute([
            ':title' => $_POST['title'],
            ':description' => $_POST['description'],
            ':date_time' => $date_time,
            ':location' => $_POST['location'],
            ':max_volunteers' => (int)$_POST['max_volunteers'],
            ':status' => 'Open',
            ':created_by' => $_SESSION['user_id']
        ]);

        $success_message = 'Event added successfully!';
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    } catch (PDOException $e) {
        $error_message = 'Error adding event: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - VolunteerHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-yellow-50">
    <div class="min-h-screen">
        <nav class="bg-white shadow-lg border-b-2 border-yellow-700">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="/soemone" class="font-bold text-xl text-yellow-700">
                            <i class="fas fa-hands-helping mr-2 text-yellow-600"></i>Sparkservices
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/soemone/pages/volunteers/dashboard.php" class="text-gray-700 hover:text-yellow-700">
                            <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                        </a>
                        <a href="/soemone/pages/volunteers/events.php" class="text-gray-700 hover:text-yellow-700">
                            <i class="fas fa-calendar-alt mr-1"></i>Events
                        </a>
                        <a href="/soemone/pages/volunteers/donations.php" class="text-gray-700 hover:text-yellow-700">
                            <i class="fas fa-hand-holding-usd mr-1"></i>Donations
                        </a>
                        <a href="/soemone/pages/volunteers/volunteers.php" class="text-gray-700 hover:text-yellow-700">
                            <i class="fas fa-users mr-1"></i>Volunteers
                        </a>
                        <a href="/soemone/pages/volunteers/profile.php" class="text-gray-700 hover:text-yellow-700">
                            <i class="fas fa-user mr-1"></i>Profile
                        </a>
                        <a href="/soemone/logout.php" class="text-gray-700 hover:text-yellow-700">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="hero-bg relative">
    <div class="absolute inset-0 bg-[url('/soemone/z1.jpg')] bg-cover bg-center z-0"></div>
    <div class="absolute inset-0 bg-yellow-800 bg-opacity-80 z-10"></div>
    <div class="relative max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8 hero-content z-20">
        <div class="text-center text-white">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                Add New Event
            </h1>
            <p class="mt-6 max-w-2xl mx-auto text-xl text-amber-100">
                Create a new volunteer event for the community.
            </p>
        </div>
    </div>
</div>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg rounded-lg p-6 border border-gray-200">
                <?php if ($success_message): ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Event Title</label>
                        <input type="text" name="title" id="title" required
                            class="mt-1 block w-full sm:text-sm border-2 border-gray-300 rounded-md h-10 px-3 focus:border-yellow-500 focus:shadow-lg outline-none transition duration-200">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="4" required
                        class="mt-1 block w-full sm:text-sm border-2 border-gray-300 rounded-md h-20 p-3 focus:border-yellow-500 focus:shadow-lg outline-none transition duration-200"></textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="event_date" class="block text-sm font-medium text-gray-700">Event Date</label>
                            <input type="date" name="event_date" id="event_date" required
                            class="mt-1 block w-full sm:text-sm border-2 border-gray-300 rounded-md h-10 px-3 focus:border-yellow-500 focus:shadow-lg outline-none transition duration-200">
                        </div>

                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time</label>
                            <input type="time" name="start_time" id="start_time" required
                            class="mt-1 block w-full sm:text-sm border-2 border-gray-300 rounded-md h-10 px-3 focus:border-yellow-500 focus:shadow-lg outline-none transition duration-200">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                            <input type="text" name="location" id="location" required
                            class="mt-1 block w-full sm:text-sm border-2 border-gray-300 rounded-md h-10 px-3 focus:border-yellow-500 focus:shadow-lg outline-none transition duration-200">
                        </div>

                        <div>
                            <label for="max_volunteers" class="block text-sm font-medium text-gray-700">Maximum Volunteers</label>
                            <input type="number" name="max_volunteers" id="max_volunteers" min="1" required
                            class="mt-1 block w-full sm:text-sm border-2 border-gray-300 rounded-md h-10 px-3 focus:border-yellow-500 focus:shadow-lg outline-none transition duration-200">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="/soemone/pages/volunteers/events.php" 
                            class="inline-flex items-center px-4 py-2 border-2 border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 hover:shadow-md outline-none transition duration-200">
                            Cancel
                        </a>
                        <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border-2 border-transparent text-sm font-medium rounded-md text-white bg-yellow-700 hover:bg-yellow-800 hover:shadow-md outline-none transition duration-200">
                            Add Event
                            <i class="fas fa-plus ml-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <footer class="bg-yellow-700 text-white shadow mt-12">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <p class="text-center text-yellow-100 text-sm">
                    &copy; <?php echo date('Y'); ?> Sparkservices. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</body>
</html>
