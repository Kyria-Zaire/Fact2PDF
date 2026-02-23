<?php
/**
 * Database - Singleton PDO
 *
 * Fournit une connexion PDO unique (pattern Singleton).
 * Toutes les requêtes passent par des prepared statements.
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Constructeur privé : initialise la connexion PDO.
     */
    private function __construct()
    {
        $config = require ROOT_PATH . '/config/database.php';

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            // Ne pas exposer les détails de connexion en prod
            throw new RuntimeException('Erreur de connexion à la base de données.');
        }
    }

    /** Empêche le clonage du singleton. */
    private function __clone() {}

    /**
     * Retourne l'instance unique de Database.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne l'objet PDO brut.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Prépare et exécute une requête avec paramètres liés.
     *
     * @param string $sql    Requête SQL avec placeholders (:param ou ?)
     * @param array  $params Paramètres à lier
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Récupère toutes les lignes d'une requête.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Récupère une seule ligne.
     */
    public function fetchOne(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Retourne l'ID du dernier enregistrement inséré.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}
