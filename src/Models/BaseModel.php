<?php
/**
 * BaseModel - Classe mère de tous les modèles
 *
 * Fournit les opérations CRUD de base via PDO.
 * Chaque modèle définit $table et $fillable.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

abstract class BaseModel
{
    /** @var string Nom de la table SQL */
    protected string $table = '';

    /** @var string Clé primaire */
    protected string $primaryKey = 'id';

    /** @var array Colonnes autorisées en écriture (whitelist anti mass-assignment) */
    protected array $fillable = [];

    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Retourne tous les enregistrements.
     */
    public function all(string $orderBy = 'id', string $dir = 'DESC'): array
    {
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` ORDER BY `{$orderBy}` {$dir}"
        );
    }

    /**
     * Trouve un enregistrement par son ID.
     */
    public function find(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Insère un nouvel enregistrement (seuls les champs $fillable sont acceptés).
     *
     * @return int ID de l'enregistrement créé
     */
    public function create(array $data): int
    {
        $filtered = $this->filter($data);
        $columns  = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($filtered)));
        $placeholders = implode(', ', array_map(fn($c) => ":{$c}", array_keys($filtered)));

        $this->db->query(
            "INSERT INTO `{$this->table}` ({$columns}) VALUES ({$placeholders})",
            $filtered
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Met à jour un enregistrement.
     */
    public function update(int $id, array $data): bool
    {
        $filtered = $this->filter($data);
        $sets = implode(', ', array_map(fn($c) => "`{$c}` = :{$c}", array_keys($filtered)));
        $filtered['_id'] = $id;

        $stmt = $this->db->query(
            "UPDATE `{$this->table}` SET {$sets} WHERE `{$this->primaryKey}` = :_id",
            $filtered
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Supprime un enregistrement.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->query(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id",
            ['id' => $id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Filtre les données selon $fillable (whitelist).
     */
    protected function filter(array $data): array
    {
        return array_filter(
            $data,
            fn($key) => in_array($key, $this->fillable, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
