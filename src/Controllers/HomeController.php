<?php

namespace splitbrain\JiraDash\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class HomeController
 * @package CosmoCode\PMIDashboard\Controllers
 */
class HomeController extends BaseController
{

    /**
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response)
    {

        return $this->view->render($response, 'home.twig', [
            'projects' => $this->getProjects(),
        ]);
    }

    /**
     * Returnns all the available projects
     *
     * @return string[]
     */
    protected function getProjects()
    {
        $list = glob($this->container->config->getDataDir() . '*.sqlite');
        $list = array_map(function ($in) {
            return basename($in, '.sqlite');
        }, $list);
        sort($list);
        return $list;
    }
}
