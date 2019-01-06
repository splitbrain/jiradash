<?php

namespace splitbrain\JiraDash\Utilities;

/**
 * Class SqlHelper
 *
 * Provides some helper method to execute raw queries
 */
class SqlHelper
{
    protected $pdo;

    /**
     * SqlHelper constructor.
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Access the PDO object
     *
     * @return \PDO
     */
    public function pdo()
    {
        return $this->pdo;
    }

    /**
     * @see PDO::beginTransaction()
     * @return bool
     */
    public function begin()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * @see PDO::commit()
     * @return bool
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * @see PDO::rollBack()
     * @return bool
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    /**
     * @param string $sql
     * @param array $parameters
     * @return array
     */
    public function queryAll($sql, $parameters = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);
        return $stmt->fetchAll();
    }

    /**
     * Query exacly two parameters as key/value pairs
     *
     * @param string $sql
     * @param array $parameters
     * @return array associative array
     */
    public function queryKeyVal($sql, $parameters = [])
    {
        $list = [];
        $all = $this->queryAll($sql, $parameters);
        foreach ($all as $row) {
            list($key, $value) = array_values($row);
            $list[$key] = $value;
        }
        return $list;
    }

    /**
     * Query one single row
     *
     * @param string $sql
     * @param array $parameters
     * @return array|null
     */
    public function queryRecord($sql, $parameters = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);
        $row = $stmt->fetch();

        if (is_array($row) && count($row)) return $row;
        return null;
    }

    /**
     * Insert a given record into a table
     *
     * @param string $table
     * @param array $record
     * @return int
     */
    public function insertRecord($table, $record)
    {
        $keys = array_keys($record);
        $values = array_values($record);

        $table = $this->pdo->quote($table, \PDO::PARAM_STMT);
        $keys = array_map(function ($in) {
            return $this->pdo->quote($in, \PDO::PARAM_STMT);
        }, $keys);
        $keys = implode(',', $keys);

        $pl = implode(',', array_fill(0, count($values), '?'));

        $sql = "INSERT OR REPLACE INTO $table ($keys) VALUES ($pl)";
        return $this->exec($sql, $values);
    }

    /**
     * Query for exactly one single value
     *
     * @param string $sql
     * @param array $parameters
     * @return mixed|null
     */
    public function querySingleValue($sql, $parameters = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);
        $row = $stmt->fetch();

        if (is_array($row) && count($row)) return array_values($row)[0];
        return null;
    }

    /**
     * Query for a list of values
     *
     * the first column in the result is returned as a list
     *
     * @param string $sql
     * @param array $parameters
     * @return array
     */
    public function queryList($sql, $parameters = [])
    {
        $list = [];

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);

        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $list[] = $row[0];
        }
        return $list;
    }

    /**
     * Execute a statement
     *
     * Returns the last insert ID on INSERTs or the number of affected rows
     *
     * @param string $sql
     * @param array $parameters
     * @return int
     */
    public function exec($sql, $parameters = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);

        $count = $stmt->rowCount();
        if ($count && preg_match('/^INSERT /i', $sql)) {
            return $this->pdo->lastInsertId();
        }

        return $count;
    }
}
