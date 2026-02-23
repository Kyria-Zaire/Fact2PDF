<?php

declare(strict_types=1);

namespace App\Models;

class Project extends BaseModel
{
    protected string $table = 'projects';

    protected array $fillable = [
        'client_id', 'invoice_id', 'name', 'description',
        'status', 'priority', 'start_date', 'end_date', 'timeline', 'created_by'
    ];

    /** Statuts ordonnés pour la timeline Kanban */
    public const STATUSES = ['todo', 'in_progress', 'review', 'done', 'archived'];

    /** Labels affichés */
    public const STATUS_LABELS = [
        'todo'        => 'À faire',
        'in_progress' => 'En cours',
        'review'      => 'En revue',
        'done'        => 'Terminé',
        'archived'    => 'Archivé',
    ];

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public const PRIORITY_LABELS = [
        'low'      => 'Basse',
        'medium'   => 'Normale',
        'high'     => 'Haute',
        'critical' => 'Critique',
    ];

    /**
     * Tous les projets avec info client + facture + progression de la timeline.
     */
    public function allWithDetails(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT p.*,
                    c.name          AS client_name,
                    c.logo_path     AS client_logo,
                    i.number        AS invoice_number,
                    i.status        AS invoice_status
             FROM projects p
             JOIN clients c ON c.id = p.client_id
             LEFT JOIN invoices i ON i.id = p.invoice_id
             ORDER BY
                FIELD(p.priority, "critical","high","medium","low"),
                p.updated_at DESC'
        );

        // Calculer le % de progression depuis le JSON timeline
        foreach ($rows as &$row) {
            $row['progress']  = $this->computeProgress($row['timeline']);
            $row['timeline']  = $row['timeline'] ? json_decode($row['timeline'], true) : [];
            $row['is_late']   = $this->isLate($row);
        }

        return $rows;
    }

    /**
     * Projets d'un client.
     */
    public function byClient(int $clientId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT p.*, i.number AS invoice_number
             FROM projects p
             LEFT JOIN invoices i ON i.id = p.invoice_id
             WHERE p.client_id = :cid
             ORDER BY p.updated_at DESC',
            ['cid' => $clientId]
        );

        foreach ($rows as &$row) {
            $row['progress'] = $this->computeProgress($row['timeline']);
            $row['timeline'] = $row['timeline'] ? json_decode($row['timeline'], true) : [];
        }

        return $rows;
    }

    /**
     * Statistiques pour le dashboard.
     */
    public function stats(): array
    {
        return $this->db->fetchOne(
            'SELECT
                COUNT(*)                                              AS total,
                SUM(status = "in_progress")                          AS in_progress,
                SUM(status = "done")                                  AS done,
                SUM(status NOT IN ("done","archived") AND end_date < CURDATE()) AS late
             FROM projects'
        ) ?: [];
    }

    /**
     * Met à jour uniquement le champ timeline (JSON).
     */
    public function updateTimeline(int $id, array $steps): bool
    {
        $stmt = $this->db->query(
            'UPDATE projects SET timeline = :tl, updated_at = NOW() WHERE id = :id',
            ['tl' => json_encode($steps, JSON_UNESCAPED_UNICODE), 'id' => $id]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Calcule le % de complétion d'après le JSON timeline.
     */
    public function computeProgress(?string $timelineJson): int
    {
        if (!$timelineJson) {
            return 0;
        }

        $steps = json_decode($timelineJson, true);
        if (empty($steps) || !is_array($steps)) {
            return 0;
        }

        $done = count(array_filter($steps, fn($s) => !empty($s['done'])));
        return (int) round($done / count($steps) * 100);
    }

    /**
     * Vérifie si un projet est en retard.
     */
    private function isLate(array $row): bool
    {
        if (in_array($row['status'], ['done', 'archived'], true)) {
            return false;
        }
        return !empty($row['end_date']) && strtotime($row['end_date']) < time();
    }
}
