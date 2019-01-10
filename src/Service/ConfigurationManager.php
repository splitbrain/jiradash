<?php

namespace splitbrain\JiraDash\Service;

use Nette\Neon\Neon;

/**
 * Class ConfigurationManager
 *
 * Processes and gives access to the configuration
 */
class ConfigurationManager
{
    /** @var string where is the config data? */
    protected $confdir = __DIR__ . '/../../conf/';

    /** @var array parsed configuration */
    protected $conf = [];

    /**
     * ConfigurationManager constructor.
     *
     * Handles the deafult and local version of the config
     */
    public function __construct()
    {
        foreach (['default', 'local'] as $base) {
            $file = $this->confdir . $base . '.neon';
            if (!file_exists($file)) continue;
            $this->conf = array_replace_recursive($this->conf, Neon::decode(file_get_contents($file)));
        }
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->conf;
    }

    /**
     * Where the databases arestored
     *
     * @return string
     */
    public function getDataDir()
    {
        return __DIR__ . '/../../data/';
    }

    /**
     * Where the resources are stored
     *
     * @return string
     */
    public function getResourcesDir()
    {
        return __DIR__ . '/../../resources/';
    }

}
