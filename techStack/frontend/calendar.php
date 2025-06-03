<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Set default timezone
date_default_timezone_set('UTC');

// Handle month navigation
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Adjust for invalid month values
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// Get first day of month and total days
$first_day = mktime(0, 0, 0, $month, 1, $year);
$total_days = date('t', $first_day);
$starting_day = date('w', $first_day); // 0=Sunday, 6=Saturday

// Get user's events with improved error handling
$events = [];
try {
    // First check if all required tables exist
    $required_tables = ['class_schedules', 'subject_sessions', 'assignments', 'enrollments', 'subject_enrollments'];
    foreach ($required_tables as $table) {
        $result = $conn->query("SELECT 1 FROM $table LIMIT 1");
        if ($result === false) {
            throw new Exception("Table $table does not exist");
        }
    }

    $stmt = $conn->prepare("
        (SELECT 
            c.class_name AS title, 
            sc.schedule_date AS event_date,
            'class' AS type,
            c.id AS class_id,
            NULL AS subject_id,
            NULL AS assignment_id,
            sc.topic AS description
        FROM class_schedules sc
        JOIN classes c ON sc.class_id = c.id
        JOIN enrollments e ON c.id = e.class_id
        WHERE e.user_id = ? AND MONTH(sc.schedule_date) = ? AND YEAR(sc.schedule_date) = ?)
        
        UNION ALL
        
        (SELECT 
            s.subject_name AS title,
            ss.session_date AS event_date,
            'subject' AS type,
            s.class_id AS class_id,
            s.id AS subject_id,
            NULL AS assignment_id,
            ss.topic AS description
        FROM subject_sessions ss
        JOIN subjects s ON ss.subject_id = s.id
        JOIN subject_enrollments se ON s.id = se.subject_id
        WHERE se.user_id = ? AND MONTH(ss.session_date) = ? AND YEAR(ss.session_date) = ?)
        
        UNION ALL
        
        (SELECT 
            a.assignment_title AS title,
            a.due_date AS event_date,
            'assignment' AS type,
            s.class_id AS class_id,
            a.subject_id AS subject_id,
            a.id AS assignment_id,
            a.description AS description
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        JOIN subject_enrollments se ON a.subject_id = se.subject_id
        WHERE se.user_id = ? AND MONTH(a.due_date) = ? AND YEAR(a.due_date) = ?)
        
        ORDER BY event_date
    ");
    
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $bind_result = $stmt->bind_param("iiiiiiiii", $user_id, $month, $year, $user_id, $month, $year, $user_id, $month, $year);
    if ($bind_result === false) {
        throw new Exception("Failed to bind parameters: " . $stmt->error);
    }
    
    $execute_result = $stmt->execute();
    if ($execute_result === false) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result === false) {
        throw new Exception("Failed to get result: " . $stmt->error);
    }

    while ($row = $result->fetch_assoc()) {
        $day = date('j', strtotime($row['event_date']));
        $events[$day][] = $row;
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Calendar Error: " . $e->getMessage());
    $calendar_error = "Could not load calendar data. Please try again later. Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - EduPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
        }
        
        .calendar-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .calendar-day {
            height: 120px;
            border: 1px solid #dee2e6;
        }
        
        .today {
            background-color: rgba(76, 201, 240, 0.1);
            border: 2px solid var(--accent-color);
        }
        
        .event-class { background-color: rgba(67, 97, 238, 0.1); border-left: 3px solid var(--primary-color); }
        .event-subject { background-color: rgba(40, 167, 69, 0.1); border-left: 3px solid #28a745; }
        .event-assignment { background-color: rgba(220, 53, 69, 0.1); border-left: 3px solid #dc3545; }
    </style>
</head>
<body>
    <!-- Navigation (same as before) -->
    <!-- ... -->

    <!-- Main Content -->
    <div class="container py-5">
        <?php if (isset($calendar_error)): ?>
            <div class="alert alert-danger"><?= $calendar_error ?></div>
        <?php endif; ?>
        
        <!-- Calendar Header -->
        <div class="card mb-4">
            <div class="card-header calendar-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?= date('F Y', $first_day) ?>
                    </h3>
                    <div>
                        <a href="calendar.php?month=<?= $month-1 ?>&year=<?= ($month == 1) ? $year-1 : $year ?>" 
                           class="btn btn-light">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <a href="calendar.php?month=<?= date('n') ?>&year=<?= date('Y') ?>" 
                           class="btn btn-light mx-2">
                            Today
                        </a>
                        <a href="calendar.php?month=<?= $month+1 ?>&year=<?= ($month == 12) ? $year+1 : $year ?>" 
                           class="btn btn-light">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Calendar Grid -->
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">Sun</th>
                            <th class="text-center">Mon</th>
                            <th class="text-center">Tue</th>
                            <th class="text-center">Wed</th>
                            <th class="text-center">Thu</th>
                            <th class="text-center">Fri</th>
                            <th class="text-center">Sat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $day = 1;
                        $current_date = date('Y-m-d');
                        
                        for ($i = 0; $i < 6; $i++) {
                            if ($day > $total_days) break;
                            echo '<tr>';
                            
                            for ($j = 0; $j < 7; $j++) {
                                $is_today = ($day == date('j') && $month == date('n') && $year == date('Y'));
                                $cell_class = $is_today ? 'today' : '';
                                
                                if (($i == 0 && $j < $starting_day) || $day > $total_days) {
                                    echo '<td class="calendar-day bg-light"></td>';
                                } else {
                                    echo '<td class="calendar-day ' . $cell_class . '">';
                                    echo '<div class="fw-bold mb-1">' . $day . '</div>';
                                    
                                    // Display events for this day
                                    if (isset($events[$day])) {
                                        foreach ($events[$day] as $event) {
                                            $event_class = '';
                                            $icon = '';
                                            
                                            switch ($event['type']) {
                                                case 'class':
                                                    $event_class = 'event-class';
                                                    $icon = '<i class="fas fa-users me-1"></i>';
                                                    break;
                                                case 'subject':
                                                    $event_class = 'event-subject';
                                                    $icon = '<i class="fas fa-book me-1"></i>';
                                                    break;
                                                case 'assignment':
                                                    $event_class = 'event-assignment';
                                                    $icon = '<i class="fas fa-tasks me-1"></i>';
                                                    break;
                                            }
                                            
                                            echo '<div class="event-item small p-1 mb-1 ' . $event_class . '">
                                                ' . $icon . htmlspecialchars(substr($event['title'], 0, 15)) . '
                                              </div>';
                                        }
                                    }
                                    
                                    echo '</td>';
                                    $day++;
                                }
                            }
                            
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Event Legend -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-around">
                    <span class="badge event-class">Class</span>
                    <span class="badge event-subject">Subject</span>
                    <span class="badge event-assignment">Assignment</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>