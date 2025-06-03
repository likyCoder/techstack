<?php
session_start();
require_once '../includes/db_connect.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Verify database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("System error. Please try again later.");
}

$user_id = (int)$_SESSION['user_id'];

// Initialize variables with safe defaults
$user = [
    'first_name' => '',
    'last_name' => '',
    'username' => '',
    'email' => '',
    'role' => 'user',
    'created_at' => date('Y-m-d H:i:s'),
    'class_count' => 0,
    'completed_lessons' => 0,
    'total_lessons' => 1 // Avoid division by zero
];
$progress = 0;
$achievements = [];
$enrolled_classes = [];

try {
    // Get user data with enhanced error handling
    $user_stmt = $conn->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM enrollments WHERE user_id = u.id) as class_count,
               (SELECT COUNT(*) FROM lesson_progress WHERE user_id = u.id AND status = 'completed') as completed_lessons,
               (SELECT COUNT(*) FROM lessons) as total_lessons
        FROM users u
        WHERE u.id = ?
    ");
    
    if (!$user_stmt) {
        throw new Exception("Database query preparation failed");
    }
    
    $user_stmt->bind_param("i", $user_id);
    
    if (!$user_stmt->execute()) {
        throw new Exception("Failed to execute user query");
    }
    
    $user_result = $user_stmt->get_result();
    if ($user_result->num_rows > 0) {
        $user = array_merge($user, $user_result->fetch_assoc());
    }
    
    $user_stmt->close();

    // Calculate progress percentage
    $progress = ($user['total_lessons'] > 0) ? 
        round(($user['completed_lessons'] / $user['total_lessons']) * 100) : 0;

    // Get achievements with fallback for class relationship
    $achievement_stmt = $conn->prepare("
        SELECT a.*, IFNULL(c.class_name, 'General') as class_name 
        FROM achievements a
        LEFT JOIN classes c ON a.class_id = c.id
        WHERE a.user_id = ?
        ORDER BY a.date_achieved DESC
        LIMIT 3
    ");
    
    if ($achievement_stmt) {
        $achievement_stmt->bind_param("i", $user_id);
        if ($achievement_stmt->execute()) {
            $achievements = $achievement_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $achievement_stmt->close();
    }

    // Get enrolled classes
    $enrolled_stmt = $conn->prepare("
        SELECT c.id, c.class_name, c.class_code 
        FROM classes c
        JOIN enrollments e ON c.id = e.class_id
        WHERE e.user_id = ?
        ORDER BY c.class_name
        LIMIT 5
    ");
    
    if ($enrolled_stmt) {
        $enrolled_stmt->bind_param("i", $user_id);
        if ($enrolled_stmt->execute()) {
            $enrolled_classes = $enrolled_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $enrolled_stmt->close();
    }

} catch (Exception $e) {
    error_log("Profile Error: " . $e->getMessage());
    // Continue with default values
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EduPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --dark-color: #1a1a2e;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
        }
        
        .progress-thin {
            height: 8px;
        }
        
        .stats-card {
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .achievement-badge {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: rgba(255, 193, 7, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffc107;
            font-size: 1.5rem;
        }
        
        .class-chip {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            border-radius: 50px;
            padding: 5px 15px;
            display: inline-block;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-4">
                <!-- Profile Card -->
                <div class="card mb-4">
                    <div class="profile-header p-4 text-center">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['first_name'] . '+' . $user['last_name']) ?>&size=200" 
                             alt="Profile" class="profile-avatar mb-3">
                        <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                        <p class="mb-0">@<?= htmlspecialchars($user['username']) ?></p>
                        <span class="badge bg-light text-dark mt-2"><?= ucfirst($user['role']) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Stats -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Learning Progress</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Course Completion</span>
                                <span><?= $progress ?>%</span>
                            </div>
                            <div class="progress progress-thin">
                                <div class="progress-bar" style="width: <?= $progress ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-4">
                                <h4 class="mb-1"><?= $user['class_count'] ?></h4>
                                <small class="text-muted">Classes</small>
                            </div>
                            <div class="col-4">
                                <h4 class="mb-1"><?= $user['completed_lessons'] ?></h4>
                                <small class="text-muted">Lessons</small>
                            </div>
                            <div class="col-4">
                                <h4 class="mb-1"><?= count($achievements) ?></h4>
                                <small class="text-muted">Achievements</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Enrolled Classes -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-book-open me-2"></i>Your Classes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($enrolled_classes)): ?>
                            <?php foreach ($enrolled_classes as $class): ?>
                                <a href="subjects.php?class_id=<?= $class['id'] ?>" class="class-chip">
                                    <?= htmlspecialchars($class['class_name']) ?>
                                </a>
                            <?php endforeach; ?>
                            <div class="mt-3">
                                <a href="classes.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">You aren't enrolled in any classes yet</p>
                            <a href="class_catalog.php" class="btn btn-sm btn-primary">Browse Classes</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <ul class="nav nav-pills mb-4" id="profileTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="pill" href="#profileDetails">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="pill" href="#achievements">Achievements</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="pill" href="#settings">Settings</a>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- Profile Details Tab -->
                    <div class="tab-pane fade show active" id="profileDetails">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mb-4">Personal Information</h4>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Account Type</label>
                                    <input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Member Since</label>
                                    <input type="text" class="form-control" value="<?= date('F j, Y', strtotime($user['created_at'])) ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Achievements Tab -->
                    <div class="tab-pane fade" id="achievements">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mb-4">Your Achievements</h4>
                                
                                <?php if (!empty($achievements)): ?>
                                    <div class="row">
                                        <?php foreach ($achievements as $achievement): ?>
                                            <div class="col-md-6 mb-4">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-start">
                                                            <div class="achievement-badge me-3">
                                                                <i class="fas fa-trophy"></i>
                                                            </div>
                                                            <div>
                                                                <h5><?= htmlspecialchars($achievement['title']) ?></h5>
                                                                <p class="text-muted"><?= htmlspecialchars($achievement['class_name']) ?> • <?= date('M j, Y', strtotime($achievement['date_achieved'])) ?></p>
                                                                <p><?= htmlspecialchars($achievement['description']) ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center">
                                        <a href="achievements.php" class="btn btn-outline-primary">View All Achievements</a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No achievements yet</h5>
                                        <p class="text-muted">Complete lessons and quizzes to earn achievements</p>
                                        <a href="classes.php" class="btn btn-primary">Browse Classes</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Settings Tab -->
                    <div class="tab-pane fade" id="settings">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mb-4">Account Settings</h4>
                                
                                <form id="settingsForm">
                                    <div class="mb-3">
                                        <label class="form-label">Notification Preferences</label>
                                        <select class="form-select" name="notifications">
                                            <option value="all">All Notifications</option>
                                            <option value="important">Important Only</option>
                                            <option value="none">No Notifications</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="darkModeSwitch" name="dark_mode">
                                        <label class="form-check-label" for="darkModeSwitch">Enable Dark Mode</label>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="emailUpdates" name="email_updates" checked>
                                        <label class="form-check-label" for="emailUpdates">Receive Email Updates</label>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Settings
                                        </button>
                                    </div>
                                </form>
                                
                                <hr class="my-4">
                                
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-danger" id="logoutBtn">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editProfileForm" action="update_profile.php" method="POST">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" placeholder="Required for changes">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" placeholder="Leave blank to keep current">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle form submissions
        document.getElementById('logoutBtn').addEventListener('click', function() {
            window.location.href = 'logout.php';
        });
        
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update profile'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update profile');
            });
        });
        
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Settings saved successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Failed to save settings'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to save settings');
            });
        });
    </script>
</body>
</html>