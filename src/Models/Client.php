<?php

declare(strict_types=1);

namespace App\Models;

class Client extends BaseModel
{
    protected string $table = 'clients';

    protected array $fillable = [
        'name', 'email', 'phone', 'address', 'city', 'postal_code',
        'country', 'logo_path', 'notes', 'created_by'
    ];

    /**
     * Retourne les clients avec le nombre de factures associées.
     */
    public function allWithStats(): array
    {
        return $this->db->fetchAll(
            'SELECT c.*,
                    COUNT(i.id)    AS invoice_count,
                    SUM(i.total)   AS total_billed
             FROM clients c
             LEFT JOIN invoices i ON i.client_id = c.id
             GROUP BY c.id
             ORDER BY c.name ASC'
        );
    }

    /**
     * Retourne les contacts d'un client.
     */
    public function contacts(int $clientId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM contacts WHERE client_id = :id ORDER BY is_primary DESC, name ASC',
            ['id' => $clientId]
        );
    }

    /**
     * Recherche par nom ou email.
     */
    public function search(string $term): array
    {
        $like = '%' . $term . '%';
        return $this->db->fetchAll(
            'SELECT * FROM clients WHERE name LIKE :term OR email LIKE :term2 ORDER BY name ASC',
            ['term' => $like, 'term2' => $like]
        );
    }
}
