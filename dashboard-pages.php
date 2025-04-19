<?php
// customer/dashboard.php
require_once '../config.php';
check_access(['customer']);

$user_id = $_SESSION['user_id'];

// Get customer profile data
$stmt = $conn->prepare("
    SELECT customer_id, first_name, last_name 
    FROM customer_profiles 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$customer_id = $customer['customer_id'];
$stmt->close();

// Get upcoming appointments
$stmt = $conn->prepare("
    SELECT a.*, 
           dp.first_name as designer_first_name, 
           dp.last_name as designer_last_name,
           d.dress_name
    FROM appointments a
    JOIN designer_profiles dp ON a.designer_id = dp.designer_id
    LEFT JOIN dresses d ON a.dress_id = d.dress_id
    WHERE a.customer_id = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 5
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result();
$stmt->close();

// Get past appointments
$stmt = $conn->prepare("
    SELECT a.*, 
           dp.first_name as designer_first_name, 
           dp.last_name as designer_last_name,
           d.dress_name
    FROM appointments a
    JOIN designer_profiles dp ON a.designer_id = dp.designer_id
    LEFT JOIN dresses d ON a.dress_id = d.dress_id
    WHERE a.customer_id = ? AND (a.appointment_date < CURDATE() OR (a.appointment_date = CURDATE() AND a.appointment_time < CURTIME()))
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$past_appointments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Dress Maria</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include '../includes/customer_header.php'; ?>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-md-3">
                <?php include '../includes/customer_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-body">
                                <h2 class="mb-4">Welcome, <?php echo htmlspecialchars($customer['first_name']); ?>!</h2>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="card text-white bg-primary">
                                            <div class="card-body text-center">
                                                <i class="fas fa-calendar-check fa-3x mb-3"></i>
                                                <?php
                                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE customer_id = ?");
                                                $stmt->bind_param("i", $customer_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                $count = $result->fetch_assoc()['count'];
                                                $stmt->close();
                                                ?>
                                                <h4><?php echo $count; ?></h4>
                                                <p class="mb-0">Total Appointments</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card text-white bg-success">
                                            <div class="card-body text-center">
                                                <i class="fas fa-calendar-day fa-3x mb-3"></i>
                                                <?php
                                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE customer_id = ? AND appointment_date >= CURDATE()");
                                                $stmt->bind_param("i", $customer_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                $count = $result->fetch_assoc()['count'];
                                                $stmt->close();
                                                ?>
                                                <h4><?php echo $count; ?></h4>
                                                <p class="mb-0">Upcoming Appointments</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card text-white bg-info">
                                            <div class="card-body text-center">
                                                <i class="fas fa-comments fa-3x mb-3"></i>
                                                <?php
                                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE customer_id = ?");
                                                $stmt->bind_param("i", $customer_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                $count = $result->fetch_assoc()['count'];
                                                $stmt->close();
                                                ?>
                                                <h4><?php echo $count; ?></h4>
                                                <p class="mb-0">Reviews Given</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Upcoming Appointments</h5>
                                <a href="appointments.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if ($upcoming_appointments->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Designer</th>
                                                    <th>Dress</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($appointment = $upcoming_appointments->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        echo date('M d, Y', strtotime($appointment['appointment_date'])); 
                                                        echo '<br>';
                                                        echo date('h:i A', strtotime($appointment['appointment_time'])); 
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($appointment['designer_first_name'] . ' ' . $appointment['designer_last_name']); ?></td>
                                                    <td><?php echo $appointment['dress_id'] ? htmlspecialchars($appointment['dress_name']) : 'N/A'; ?></td>
                                                    <td>
                                                        <?php 
                                                            switch($appointment['status']) {
                                                                case 'confirmed':
                                                                    echo '<span class="badge bg-success">Confirmed</span>';
                                                                    break;
                                                                case 'pending':
                                                                    echo '<span class="badge bg-warning text-dark">Pending</span>';
                                                                    break;
                                                                case 'cancelled':
                                                                    echo '<span class="badge bg-danger">Cancelled</span>';
                                                                    break;
                                                                default:
                                                                    echo '<span class="badge bg-secondary">Unknown</span>';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a href="appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <?php if($appointment['status'] != 'cancelled'): ?>
                                                            <a href="reschedule_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-calendar-alt"></i> Reschedule
                                                            </a>
                                                            <a href="cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                                                <i class="fas fa-times-circle"></i> Cancel
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center">You have no upcoming appointments. <a href="book_appointment.php" class="btn btn-sm btn-primary">Book Now</a></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Past Appointments</h5>
                                <a href="appointments.php?filter=past" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if ($past_appointments->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Designer</th>
                                                    <th>Dress</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($appointment = $past_appointments->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        echo date('M d, Y', strtotime($appointment['appointment_date'])); 
                                                        echo '<br>';
                                                        echo date('h:i A', strtotime($appointment['appointment_time'])); 
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($appointment['designer_first_name'] . ' ' . $appointment['designer_last_name']); ?></td>
                                                    <td><?php echo $appointment['dress_id'] ? htmlspecialchars($appointment['dress_name']) : 'N/A'; ?></td>
                                                    <td>
                                                        <?php 
                                                            switch($appointment['status']) {
                                                                case 'completed':
                                                                    echo '<span class="badge bg-success">Completed</span>';
                                                                    break;
                                                                case 'no-show':
                                                                    echo '<span class="badge bg-danger">No Show</span>';
                                                                    break;
                                                                case 'cancelled':
                                                                    echo '<span class="badge bg-danger">Cancelled</span>';
                                                                    break;
                                                                default:
                                                                    echo '<span class="badge bg-secondary">Unknown</span>';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a href="appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <?php if($appointment['status'] == 'completed' && !has_review($appointment['appointment_id'], $conn)): ?>
                                                            <a href="add_review.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-star"></i> Review
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center">You have no past appointments.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Favorite Designers</h5>
                                <a href="designers.php" class="btn btn-sm btn-primary">View All Designers</a>
                            </div>
                            <div class="card-body">
                                <?php
                                $stmt = $conn->prepare("
                                    SELECT dp.designer_id, dp.first_name, dp.last_name, dp.profile_image
                                    FROM favorite_designers fd
                                    JOIN designer_profiles dp ON fd.designer_id = dp.designer_id
                                    WHERE fd.customer_id = ?
                                    LIMIT 3
                                ");
                                $stmt->bind_param("i", $customer_id);
                                $stmt->execute();
                                $favorites = $stmt->get_result();
                                $stmt->close();
                                
                                if ($favorites->num_rows > 0): ?>
                                    <div class="row">
                                        <?php while($designer = $favorites->fetch_assoc()): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card">
                                                    <img src="<?php echo !empty($designer['profile_image']) ? '../uploads/designers/' . htmlspecialchars($designer['profile_image']) : '../images/default-profile.jpg'; ?>" 
                                                         class="card-img-top" alt="<?php echo htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']); ?>">
                                                    <div class="card-body text-center">
                                                        <h6 class="card-title"><?php echo htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']); ?></h6>
                                                        <a href="designer_profile.php?id=<?php echo $designer['designer_id']; ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center">You haven't added any designers to your favorites yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Notifications</h5>
                                <a href="notifications.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php
                                $stmt = $conn->prepare("
                                    SELECT * FROM notifications
                                    WHERE user_id = ?
                                    ORDER BY created_at DESC
                                    LIMIT 5
                                ");
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $notifications = $stmt->get_result();
                                $stmt->close();
                                
                                if ($notifications->num_rows > 0): ?>
                                    <ul class="list-group">
                                        <?php while($notification = $notifications->fetch_assoc()): ?>
                                            <li class="list-group-item <?php echo $notification['is_read'] ? '' : 'list-group-item-primary'; ?>">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <?php if(!$notification['is_read']): ?>
                                                            <span class="badge bg-danger rounded-pill me-2">New</span>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($notification['message']); ?>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('M d, h:i A', strtotime($notification['created_at'])); ?></small>
                                                </div>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-center">You have no notifications.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    
    <?php
    // Helper function to check if a customer has already reviewed an appointment
    function has_review($appointment_id, $conn) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE appointment_id = ?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();
        return $count > 0;
    }
    ?>
</body>
</html>