<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendance_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database and tables
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

$conn->query("CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    roll_no VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    status VARCHAR(10) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id)
)");

$message = "";
$messageType = "";

// Handle student registration
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $roll_no = $_POST['roll_no'];
    
    $stmt = $conn->prepare("INSERT INTO students (name, roll_no) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $roll_no);
    
    if ($stmt->execute()) {
        $message = "Student registered successfully!";
        $messageType = "success";
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "danger";
    }
    $stmt->close();
}

// Handle attendance marking
if (isset($_POST['mark_attendance'])) {
    $date = $_POST['date'];
    $students = $_POST['students'] ?? [];
    
    foreach ($students as $student_id => $status) {
        $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $student_id, $date, $status);
        $stmt->execute();
        $stmt->close();
    }
    $message = "Attendance marked successfully!";
    $messageType = "success";
}

// Fetch students
$students = $conn->query("SELECT * FROM students ORDER BY roll_no");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System - PHP & MySQL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .student-row {
            background: #f8f9fa;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .checkbox-wrapper {
            display: flex;
            gap: 20px;
        }
        
        .checkbox-wrapper label {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="main-card">
        <div class="card-header-custom">
            <h3 class="mb-1">📋 Attendance System</h3>
            <p class="mb-0">Student Registration & Attendance Marking</p>
        </div>
        
        <div class="p-4">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <ul class="nav nav-pills mb-4" id="attendanceTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="register-tab" data-bs-toggle="pill" data-bs-target="#register" type="button">
                        Student Registration
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="mark-tab" data-bs-toggle="pill" data-bs-target="#mark" type="button">
                        Mark Attendance
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="view-tab" data-bs-toggle="pill" data-bs-target="#view" type="button">
                        View Students
                    </button>
                </li>
            </ul>
            
            <div class="tab-content">
                <!-- Registration Tab -->
                <div class="tab-pane fade show active" id="register" role="tabpanel">
                    <div class="card p-4">
                        <h5>➕ Register New Student</h5>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-5">
                                    <input type="text" name="name" class="form-control" placeholder="Student Name" required>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="roll_no" class="form-control" placeholder="Roll Number" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Mark Attendance Tab -->
                <div class="tab-pane fade" id="mark" role="tabpanel">
                    <div class="card p-4">
                        <h5>✅ Mark Attendance</h5>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <h6 class="mt-4">Select Students:</h6>
                            <?php
                            $studentResult = $conn->query("SELECT * FROM students ORDER BY roll_no");
                            while ($student = $studentResult->fetch_assoc()) {
                                echo '<div class="student-row">';
                                echo '<div><strong>' . $student['roll_no'] . '</strong> - ' . $student['name'] . '</div>';
                                echo '<div class="checkbox-wrapper">';
                                echo '<label><input type="radio" name="students[' . $student['id'] . ']" value="Present" checked> Present</label>';
                                echo '<label><input type="radio" name="students[' . $student['id'] . ']" value="Absent"> Absent</label>';
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                            
                            <button type="submit" name="mark_attendance" class="btn btn-success mt-3">Submit Attendance</button>
                        </form>
                    </div>
                </div>
                
                <!-- View Students Tab -->
                <div class="tab-pane fade" id="view" role="tabpanel">
                    <div class="card p-4">
                        <h5>👥 Registered Students</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Name</th>
                                    <th>Registered Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($students->num_rows > 0) {
                                    while ($student = $students->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . $student['roll_no'] . '</td>';
                                        echo '<td>' . $student['name'] . '</td>';
                                        echo '<td>' . $student['created_at'] . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="3" class="text-center">No students registered yet</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>