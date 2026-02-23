<?php
/**
 * NotificationController — Polling léger pour notifications browser
 *
 * Le frontend interroge GET /notifications/poll toutes les 30s.
 * Retourne les notifs non lues → le JS affiche un badge + toast.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;

class NotificationController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * GET /notifications/poll
     * Retourne JSON {count, items} des notifs non lues de l'utilisateur connecté.
     */
    public function poll(): void
    {
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }

        $items = $this->db->fetchAll(
            'SELECT id, type, title, body, link, created_at
             FROM notifications
             WHERE user_id = :uid AND is_read = 0
             ORDER BY created_at DESC
             LIMIT 20',
            ['uid' => $user['id']]
        );

        header('Content-Type: application/json');
        echo json_encode([
            'count' => count($items),
            'items' => $items,
        ]);
        exit;
    }

    /**
     * POST /notifications/{id}/read — Marque une notif comme lue.
     */
    public function markRead(string $id): void
    {
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            exit;
        }

        $this->db->query(
            'UPDATE notifications SET is_read = 1
             WHERE id = :id AND user_id = :uid',
            ['id' => (int) $id, 'uid' => $user['id']]
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * POST /notifications/read-all — Marque toutes comme lues.
     */
    public function markAllRead(): void
    {
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            exit;
        }

        $this->db->query(
            'UPDATE notifications SET is_read = 1 WHERE user_id = :uid',
            ['uid' => $user['id']]
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Crée une notification pour un utilisateur (appelée depuis d'autres contrôleurs).
     */
    public static function create(int $userId, string $type, string $title, string $body = '', string $link = ''): void
    {
        try {
            Database::getInstance()->query(
                'INSERT INTO notifications (user_id, type, title, body, link)
                 VALUES (:uid, :type, :title, :body, :link)',
                ['uid' => $userId, 'type' => $type, 'title' => $title, 'body' => $body, 'link' => $link]
            );
        } catch (\Exception $e) {
            logMessage('error', "Notification creation failed: " . $e->getMessage());
        }
    }
}
