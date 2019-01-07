<?php

namespace splitbrain\JiraDash\Service;

use Nette\Neon\Neon;

class ConfigurationManager
{
    protected $confdir = __DIR__ . '/../../conf/';

    protected $conf = [];

    public function __construct()
    {
        foreach (['default', 'local'] as $base) {
            $file = $this->confdir . $base . '.neon';
            if (!file_exists($file)) continue;
            $this->conf = array_replace_recursive($this->conf, Neon::decode(file_get_contents($file)));
        }
    }

    public function getConfiguration()
    {
        return $this->conf;
    }

    public function getDataDir()
    {
        return __DIR__ . '/../../data/';
    }

    public function getResourcesDir()
    {
        return __DIR__ . '/../../resources/';
    }

}
