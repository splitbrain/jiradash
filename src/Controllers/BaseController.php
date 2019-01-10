<?php

namespace splitbrain\JiraDash\Controllers;

/**
 * Class BaseController
 */
abstract class BaseController
{
    /**
     * @var \splitbrain\JiraDash\Container
     */
    protected $container;

    /**
     * @var \Slim\Views\Twig
     */
    protected $view;

    /**
     * BaseController constructor.
     * @param $container
     */
    public function __construct($container)
    {
        $this->container = $container;
        $this->view = $container->view;
    }
}
