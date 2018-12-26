<?php

namespace splitbrain\JiraDash\CLI;

use splitbrain\JiraDash\App;
use splitbrain\JiraDash\Container;
use splitbrain\phpcli\PSR3CLI;

abstract class AbstractCLI extends PSR3CLI
{
    /**
     * @var Container $container Only initialized during the main() execution!
     */
    protected $container;
    
    /** @inheritdoc */
    protected function execute()
    {
        $app = new App();
        $this->container = $app->getContainer();
        parent::execute();
    }
}
