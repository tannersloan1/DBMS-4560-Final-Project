<?php
/**
 * Activity Logger Utility
 * Logs user activities to the ACTIVITY_LOG table
 */

function logActivity($conn, $user_id, $user_role, $action_type, $action_description, $table_affected = null, $record_id = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $sql = "INSERT INTO ACTIVITY_LOG (user_id, user_role, action_type, action_description, table_affected, record_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssiiss", $user_id, $user_role, $action_type, $action_description, $table_affected, $record_id, $ip_address, $user_agent);
    
    return $stmt->execute();
}

function logLogin($conn, $user_id, $user_role) {
    return logActivity($conn, $user_id, $user_role, 'LOGIN', 'User logged into the system');
}

function logLogout($conn, $user_id, $user_role) {
    return logActivity($conn, $user_id, $user_role, 'LOGOUT', 'User logged out of the system');
}

function logRecordEdit($conn, $user_id, $user_role, $table_name, $record_id, $description) {
    return logActivity($conn, $user_id, $user_role, 'RECORD_EDIT', $description, $table_name, $record_id);
}

function logRecordCreate($conn, $user_id, $user_role, $table_name, $record_id, $description) {
    return logActivity($conn, $user_id, $user_role, 'RECORD_CREATE', $description, $table_name, $record_id);
}

function logRecordDelete($conn, $user_id, $user_role, $table_name, $record_id, $description) {
    return logActivity($conn, $user_id, $user_role, 'RECORD_DELETE', $description, $table_name, $record_id);
}

function logUserAction($conn, $user_id, $user_role, $action_type, $description) {
    return logActivity($conn, $user_id, $user_role, $action_type, $description);
}
?>
