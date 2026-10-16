<?php

namespace Emunicipal\Models;

use PDO;
use Emunicipal\Core\Database;

/**
 * BaseModel — shared database access for all models.
 *
 * All SQL queries must use PDO prepared statements.
 * Never concatenate user input into SQL strings (per Req 14.2).
 *
 * Models reflect the table structure defined in database.md.
 */
abstract class BaseModel
{
    protected PDO    $db;
    protected string $table  = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find a record by primary key.
     */
    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Return all records from the table.
     */
    public function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` ORDER BY `{$orderBy}` {$direction}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Insert a row and return the new record's ID.
     */
    public function insert(array $data): int
    {
        $columns     = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $this->table,
            implode(', ', array_map(fn($c) => "`{$c}`", $columns)),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a record by primary key.
     * Returns the number of affected rows.
     */
    public function update(int $id, array $data): int
    {
        $setParts = array_map(fn($col) => "`{$col}` = :{$col}", array_keys($data));

        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE `{$this->primaryKey}` = :_pk_id",
            $this->table,
            implode(', ', $setParts)
        );

        $data[':_pk_id'] = $id;
        // Rename keys to use : prefix for PDO
        $bound = [];
        foreach ($data as $k => $v) {
            $bound[str_starts_with($k, ':') ? $k : ":{$k}"] = $v;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bound);
        return $stmt->rowCount();
    }

    /**
     * Count rows matching optional WHERE conditions.
     */
    public function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `{$this->table}`");
            $stmt->execute();
        } else {
            $parts = array_map(fn($col) => "`{$col}` = :{$col}", array_keys($conditions));
            $sql   = "SELECT COUNT(*) FROM `{$this->table}` WHERE " . implode(' AND ', $parts);
            $stmt  = $this->db->prepare($sql);
            $stmt->execute($conditions);
        }
        return (int) $stmt->fetchColumn();
    }

    /**
     * Expose the PDO instance for complex queries in subclasses.
     */
    protected function getDb(): PDO
    {
        return $this->db;
    }
}
