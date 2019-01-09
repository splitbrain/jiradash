<?php

namespace splitbrain\JiraDash;


use splitbrain\JiraDash\Controllers\HomeController;
use splitbrain\JiraDash\Controllers\ProjectController;

/**
 * PMI Dashboard App
 */
class App
{

    /**
     * Configuration loader
     *
     * @var \splitbrain\JiraDash\Service\ConfigurationManager
     */
    protected $configurationManager;

    /**
     * Full app configuration
     *
     * @var array
     */
    protected $configuration;

    /**
     * The app
     *
     * @var \Slim\App
     */
    protected $app;

    /**
     * Dependency container
     *
     * @var Container
     */
    protected $container;


    /**
     * Application constructor
     */
    public function __construct()
    {
        $c = Container::getInstance();

        $this->app = new \Slim\App($c);
        $this->container = $this->app->getContainer();

        // auto dump container autocompletion during development
        if (class_exists(PimpleDumper::class)) {
            $this->container->register(new PimpleDumper());
        }

        $this->initRoutes();
    }

    /**
     * Initializes the routes and middlewares
     *
     * called from the constructor
     */
    protected function initRoutes()
    {
        $this->app->get('/', HomeController::class)->setName('home');
        $this->app->post('/', HomeController::class.':addProject')->setName('home-addProject');
        $this->app->any('/p/{project}', ProjectController::class)->setName('project');
    }

    /**
     * Get the DI container associated with this app
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Run application
     */
    public function run()
    {
        $this->app->run();
    }
}
