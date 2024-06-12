<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_credentials'])) {
    $new_username = $_POST['username'];
    $new_password = $_POST['password'];
    $user_id = $_SESSION['user_id'];

    $stmt = $db->prepare("UPDATE users SET username = :username, password = :password WHERE id = :id");
    $stmt->bindValue(':username', $new_username, SQLITE3_TEXT);
    $stmt->bindValue(':password', $new_password, SQLITE3_TEXT);
    $stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        $success = "Credentials updated successfully!";
    } else {
        $error = "Failed to update credentials!";
    }
}
?>


<?php
$currentMonthStart = date('Y-m-01 00:00:00');
$currentMonthEnd = date('Y-m-t 23:59:59');
$stmt = $pdo->prepare("SELECT visit_time FROM visits WHERE visit_time BETWEEN ? AND ?");
$stmt->execute([$currentMonthStart, $currentMonthEnd]);
$visits = $stmt->fetchAll();

// Initialize arrays to hold data
$hours = array_fill(0, 24, 0);
$daysOfWeek = array_fill(0, 7, 0);
$daysOfMonth = array_fill(1, date('t'), 0); // date('t') gives the number of days in the current month
$hoursByDayOfWeek = array_fill(0, 7, array_fill(0, 24, 0)); // [day][hour]
$hoursByDayOfMonth = array_fill(1, date('t'), array_fill(0, 24, 0)); // [day][hour]

foreach ($visits as $visit) {
    $timestamp = strtotime($visit['visit_time']);
    $hour = (int)date('G', $timestamp);
    $dayOfWeek = (int)date('w', $timestamp); // 0 (for Sunday) through 6 (for Saturday)
    $dayOfMonth = (int)date('j', $timestamp); // Day of the month without leading zeros

    $hours[$hour]++;
    $daysOfWeek[$dayOfWeek]++;
    $daysOfMonth[$dayOfMonth]++;
    $hoursByDayOfWeek[$dayOfWeek][$hour]++;
    $hoursByDayOfMonth[$dayOfMonth][$hour]++;
}

// Find the busiest hour, day of the week, and day of the month
$maxVisitsHour = max($hours);
$rushHour = array_search($maxVisitsHour, $hours);

$maxVisitsDayOfWeek = max($daysOfWeek);
$rushDayOfWeek = array_search($maxVisitsDayOfWeek, $daysOfWeek);

$maxVisitsDayOfMonth = max($daysOfMonth);
$rushDayOfMonth = array_search($maxVisitsDayOfMonth, $daysOfMonth);

// Find the busiest hour for each day of the week
$rushHourByDayOfWeek = array_map(function ($hours) {
    $maxVisits = max($hours);
    return array_search($maxVisits, $hours);
}, $hoursByDayOfWeek);

// Find the busiest hour for each day of the month
$rushHourByDayOfMonth = array_map(function ($hours) {
    $maxVisits = max($hours);
    return array_search($maxVisits, $hours);
}, $hoursByDayOfMonth);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Visitor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card {
            margin-bottom: 1rem;
        }

        .chart-container {
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: .25rem;
            margin-bottom: 1rem;
        }

        .list-group-item {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Visitor Dashboard</h1>
        <div class="row">
            <div class="col-md-3">
                <div class="card text-white bg-dark">
                    <div class="card-body">
                        <h5 class="card-title">Total Visits</h5>
                        <p class="card-text"><?= count($visits) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-dark">
                    <div class="card-body">
                        <h5 class="card-title">Rush Hour</h5>
                        <p class="card-text"><?= $rushHour ?>:00 with <?= $maxVisitsHour ?> visits</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-dark">
                    <div class="card-body">
                        <h5 class="card-title">Rush Day of the Week</h5>
                        <p class="card-text"><?= ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$rushDayOfWeek] ?> with <?= $maxVisitsDayOfWeek ?> visits</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-dark">
                    <div class="card-body">
                        <h5 class="card-title">Rush Day of the Month</h5>
                        <p class="card-text"><?= $rushDayOfMonth ?> with <?= $maxVisitsDayOfMonth ?> visits</p>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="row">
                <div class="chart-container">
                    <canvas id="visitChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <canvas id="dayOfWeekChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <canvas id="dayOfMonthChart"></canvas>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Rush Hour by Day of the Week</h5>
                        <ul class="list-group">
                            <?php foreach ($rushHourByDayOfWeek as $day => $hour) : ?>
                                <li class="list-group-item"><?= ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$day] ?>: <?= $hour ?>:00</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class=" card">
                    <div class="card-body">
                        <h5 class="card-title">Rush Hour by Day of the Month</h5>
                        <div class="row">
                            <?php
                            $daysInMonth = count($rushHourByDayOfMonth);
                            $daysPerColumn = ceil($daysInMonth / 3);
                            $currentDay = 1;
                            for ($col = 0; $col < 3; $col++) : ?>
                                <div class="col-md-4">
                                    <ul class="list-group">
                                        <?php for ($i = 0; $i < $daysPerColumn && $currentDay <= $daysInMonth; $i++, $currentDay++) : ?>
                                            <li class="list-group-item"><?= $currentDay ?>th: <?= $rushHourByDayOfMonth[$currentDay] ?>:00</li>
                                        <?php endfor; ?>
                                    </ul>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="container w-50 text-center my-5 border border-3">
        <h2>Update Credentials</h2>
        <?php if (isset($success)) : ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" action="index.php">
            <div class="form-group row">
                <div class="col">
                    <label for="username">New Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="col">
                    <label for="password">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>
            <button type="submit" name="update_credentials" class="btn btn-primary mt-2">Update</button>
        </form>
        <form method="post" action="logout.php" >
            <button type="submit" class="btn btn-danger w-100 mt-5 mb-1">Logout</button>
        </form>
    </div>

    <script>
        const visitData = <?= json_encode($hours) ?>;
        const dayOfWeekData = <?= json_encode($daysOfWeek) ?>;
        const dayOfMonthData = <?= json_encode($daysOfMonth) ?>;

        // Chart for hourly visits
        const visitChartCtx = document.getElementById('visitChart').getContext('2d');
        new Chart(visitChartCtx, {
            type: 'bar',
            data: {
                labels: [...Array(24).keys()].map(hour => `${hour}:00`),
                datasets: [{
                    label: 'Visits by Hour',
                    data: visitData,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Chart for day of the week visits
        const dayOfWeekChartCtx = document.getElementById('dayOfWeekChart').getContext('2d');
        new Chart(dayOfWeekChartCtx, {
            type: 'bar',
            data: {
                labels: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                datasets: [{
                    label: 'Visits by Day of the Week',
                    data: dayOfWeekData,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        
        // Chart for day of the month visits
        const dayOfMonthChartCtx = document.getElementById('dayOfMonthChart').getContext('2d');
        new Chart(dayOfMonthChartCtx, {
            type: 'bar',
            data: {
                labels: [...Array(dayOfMonthData.length).keys()].map(day => day + 1),
                datasets: [{
                    label: 'Visits by Day of the Month',
                    data: dayOfMonthData,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>

</html>