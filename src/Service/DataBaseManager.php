<?php

namespace splitbrain\JiraDash\Service;

use splitbrain\JiraDash\Container;
use splitbrain\JiraDash\Utilities\SqlHelper;

/**
 * Class DataBaseManager
 *
 * Gives access to the databases
 */
class DataBaseManager
{
    /** @var Container */
    protected $container;
    /** @var SqlHelper[] */
    protected $connections;

    /** @var string */
    protected $schemafile;

    /**
     * DataBaseManager constructor.
     *
     * @param Container $c
     */
    public function __construct(Container $c)
    {
        $this->container = $c;
        $this->schemafile = $this->container->config->getResourcesDir() . 'db.sql';
    }

    /**
     * Opens the database for the given project
     *
     * @param string $project
     * @param bool $create should the database be created ifit doesn't exist?
     * @return SqlHelper
     * @throws \Exception
     */
    public function accessDB($project, $create = false)
    {
        if (isset($this->connections[$project])) {
            return $this->connections[$project];
        }

        $dbdir = $this->container->config->getDataDir();
        $dbfile = $dbdir . $project . '.sqlite';
        if (file_exists($dbfile) && filesize($dbfile) > 0) {
            // rebuild whole database if schemafile was updated
            if ($create && filemtime($this->schemafile) > filemtime($dbfile)) {
                unlink($dbfile);
            }
        } else {
            if (!$create) {
                throw new \Exception('no database file');
            }
        }

        $pdo = new \PDO('sqlite:' . $dbfile);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->connections[$project] = new SqlHelper($pdo);
        if ($create && (!file_exists($dbfile) || filesize($dbfile) == 0)) {
            $this->create($this->connections[$project]);
        }

        return $this->connections[$project];
    }

    /**
     * Creates a new database and fills it with the schema
     *
     * @param SqlHelper $db
     */
    protected function create(SqlHelper $db)
    {
        $sql = file_get_contents($this->schemafile);
        $db->begin();
        $db->pdo()->exec($sql);
        $db->commit();
    }

    /**
     * Returns all the available projects
     *
     * @return array basename => [time => timestamp, size => size]
     */
    public function getProjects()
    {
        $files = glob($this->container->config->getDataDir() . '*.sqlite');
        $list = [];
        foreach ($files as $file) {
            $list[basename($file, '.sqlite')] = [
                'time' => filemtime($file),
                'size' => filesize($file),
            ];
        }
        ksort($list);
        return $list;
    }
}
