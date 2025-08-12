<?php
// Get current user notifications
$current_user = $user->get_current_user();
$unread_count = 0;
$recent_notifications = [];

if ($current_user) {
    try {
        $unread_count = $notification->get_unread_count($current_user['user_id']);
        $recent_notifications = $notification->get_user_notifications($current_user['user_id'], 5);
    } catch (Exception $e) {
        // If notification system fails, continue without notifications
        error_log("Notification system error: " . $e->getMessage());
        $unread_count = 0;
        $recent_notifications = [];
    }
}
?>

<!-- Notification Dropdown -->
<div class="dropdown">
    <button class="btn btn-outline-secondary position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-bell"></i>
        <?php if ($unread_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo $unread_count > 99 ? '99+' : $unread_count; ?>
                <span class="visually-hidden">unread notifications</span>
            </span>
        <?php endif; ?>
    </button>
    
    <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
        <div class="dropdown-header d-flex justify-content-between align-items-center">
            <span>Notifications</span>
            <?php if ($unread_count > 0): ?>
                <button class="btn btn-sm btn-link p-0" onclick="markAllNotificationsAsRead()">
                    Mark all as read
                </button>
            <?php endif; ?>
        </div>
        
        <div id="notification-list">
            <?php if (empty($recent_notifications)): ?>
                <div class="dropdown-item text-center text-muted py-3">
                    <i class="bi bi-bell-slash"></i>
                    <div>No notifications</div>
                </div>
            <?php else: ?>
                <?php foreach ($recent_notifications as $notif): ?>
                    <?php
                    $notification_id = isset($notif['id']) ? $notif['id'] : '0';
                    $transaction_id = isset($notif['transaction_id']) ? $notif['transaction_id'] : '';
                    $is_read = isset($notif['is_read']) ? $notif['is_read'] : 0;
                    $title = isset($notif['title']) ? $notif['title'] : 'Notification';
                    $message = isset($notif['message']) ? $notif['message'] : '';
                    $created_at = isset($notif['created_at']) ? $notif['created_at'] : '';
                    $type = isset($notif['type']) ? $notif['type'] : 'general';
                    ?>
                    <div class="dropdown-item notification-item <?php echo $is_read ? '' : 'unread'; ?>"
                         data-notification-id="<?php echo $notification_id; ?>"
                         onclick="handleNotificationClick(<?php echo $notification_id; ?>, '<?php echo $transaction_id; ?>')">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-2">
                                <i class="bi bi-<?php echo $notification->get_notification_icon($type); ?> text-<?php echo $notification->get_notification_color($type); ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?php echo htmlspecialchars($title); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($message); ?></div>
                                <div class="text-muted small"><?php echo $created_at ? time_ago($created_at) : 'Unknown time'; ?></div>
                            </div>
                            <?php if (!$is_read): ?>
                                <div class="flex-shrink-0">
                                    <span class="badge bg-primary rounded-pill">&nbsp;</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="dropdown-divider"></div>
                <div class="dropdown-item text-center">
                    <a href="#" onclick="showAllNotifications()" class="text-decoration-none">
                        View all notifications
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.notification-dropdown .notification-item {
    border: none;
    padding: 12px 16px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-dropdown .notification-item:hover {
    background-color: #f8f9fa;
}

.notification-dropdown .notification-item.unread {
    background-color: #e3f2fd;
}

.notification-dropdown .notification-item.unread:hover {
    background-color: #bbdefb;
}
</style>

<script>
// Notification JavaScript functions
function markAllNotificationsAsRead() {
    fetch('../model/manageNotifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=mark_all_as_read'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh notifications
            refreshNotifications();
        } else {
            console.error('Failed to mark notifications as read:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function handleNotificationClick(notificationId, transactionId) {
    // Mark as read
    fetch('../model/manageNotifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=mark_as_read&notification_id=${notificationId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            document.querySelector(`[data-notification-id="${notificationId}"]`).classList.remove('unread');
            updateNotificationBadge();
            
            // Navigate to transaction if available
            if (transactionId) {
                window.location.href = `/user_transactions/${transactionId}`;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function refreshNotifications() {
    fetch('../model/manageNotifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_notifications&limit=5'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationList(data.notifications);
            updateNotificationBadge(data.unread_count);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updateNotificationBadge(count = null) {
    if (count === null) {
        // Fetch current count
        fetch('../model/manageNotifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_unread_count'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.unread_count);
            }
        });
        return;
    }
    
    const badge = document.querySelector('#notificationDropdown .badge');
    if (count > 0) {
        if (badge) {
            badge.textContent = count > 99 ? '99+' : count;
        } else {
            // Create badge if it doesn't exist
            const button = document.querySelector('#notificationDropdown');
            const newBadge = document.createElement('span');
            newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
            newBadge.innerHTML = `${count > 99 ? '99+' : count}<span class="visually-hidden">unread notifications</span>`;
            button.appendChild(newBadge);
        }
    } else {
        if (badge) {
            badge.remove();
        }
    }
}

function showAllNotifications() {
    // This can be implemented to show a full notifications page
    console.log('Show all notifications - to be implemented');
}

// Auto-refresh notifications every 30 seconds
setInterval(refreshNotifications, 30000);
</script>

<?php
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
