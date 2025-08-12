<?php
require_once("../controller/main.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

// Get current user
$current_user = $user->get_current_user();
if (!$current_user) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $current_user['user_id'];

try {
    switch ($action) {
        case 'get_notifications':
            $limit = $_POST['limit'] ?? 10;
            $unread_only = isset($_POST['unread_only']) && $_POST['unread_only'] == '1';
            
            $notifications = $notification->get_user_notifications($user_id, $limit, $unread_only);
            $unread_count = $notification->get_unread_count($user_id);
            
            // Add display information to notifications
            foreach ($notifications as &$notif) {
                $notif['icon'] = $notification->get_notification_icon($notif['type']);
                $notif['color'] = $notification->get_notification_color($notif['type']);
                $notif['time_ago'] = time_ago($notif['created_at']);
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ]);
            break;

        case 'mark_as_read':
            $notification_id = $_POST['notification_id'] ?? '';
            
            if (empty($notification_id)) {
                echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
                exit;
            }
            
            $result = $notification->mark_as_read($notification_id, $user_id);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
            }
            break;

        case 'mark_all_as_read':
            $result = $notification->mark_all_as_read($user_id);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
            }
            break;

        case 'delete_notification':
            $notification_id = $_POST['notification_id'] ?? '';
            
            if (empty($notification_id)) {
                echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
                exit;
            }
            
            $result = $notification->delete_notification($notification_id, $user_id);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Notification deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
            }
            break;

        case 'get_unread_count':
            $unread_count = $notification->get_unread_count($user_id);
            echo json_encode(['success' => true, 'unread_count' => $unread_count]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }

} catch (Exception $e) {
    error_log("Error managing notifications: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

/**
 * Helper function to calculate time ago
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', strtotime($datetime));
    }
}
?>
