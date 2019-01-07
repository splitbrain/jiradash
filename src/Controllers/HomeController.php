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

        return $this->view->render($response, 'home.twig', []);
    }


}
