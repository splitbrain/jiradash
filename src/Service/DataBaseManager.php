<?php

namespace splitbrain\JiraDash\Service;

use splitbrain\JiraDash\Container;
use splitbrain\JiraDash\Utilities\SqlHelper;

class DataBaseManager
{
    /** @var Container */
    protected $container;
    /** @var SqlHelper[] */
    protected $connections;

    /**
     * DataBaseManager constructor.
     * @param Container $c
     */
    public function __construct(Container $c)
    {
        $this->container = $c;
    }

    /**
     * @param string $project
     * @param bool $create
     * @return SqlHelper
     * @throws \Exception
     */
    public function accessDB($project, $create = false)
    {
        if(isset($this->connections[$project])) {
            return $this->connections[$project];
        }

        $dbdir = $this->container->config->getDataDir();
        $dbfile = $dbdir . $project . '.sqlite';
        if (!file_exists($dbfile)) {
            throw new \Exception('no database file and migrations not in place FIXME');
        }

        $pdo = new \PDO('sqlite:' . $dbfile);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->connections[$project] = new SqlHelper($pdo);
        return $this->connections[$project];
    }


}
