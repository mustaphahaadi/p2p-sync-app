<?php
/**
 * Audit Logging Helper
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Logs an administrative action to the audit_logs table
 *
 * @param int $userId The ID of the user performing the action
 * @param string $action The action performed (e.g. 'created', 'updated', 'deleted')
 * @param string $entityType The type of entity (e.g. 'event', 'user')
 * @param int|null $entityId The ID of the entity affected
 * @param string|array $details Extra details to log
 * @return bool True if logged successfully
 */
function logAuditAction($userId, $action, $entityType, $entityId = null, $details = '') {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $detailsStr = is_array($details) ? json_encode($details) : (string)$details;
        
        return $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $detailsStr
        ]);
    } catch (PDOException $e) {
        // Failing to audit log shouldn't necessarily crash the whole app,
        // but we can log it to the system error log.
        error_log("Failed to insert audit log: " . $e->getMessage());
        return false;
    }
}
