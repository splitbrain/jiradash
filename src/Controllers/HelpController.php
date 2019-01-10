<?php

namespace splitbrain\JiraDash\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class HelpController
 *
 * Display the help screen
 */
class HelpController extends BaseController
{

    /**
     * Display the home screen
     *
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response)
    {
        return $this->view->render($response, 'help.twig', ['title' => 'Help']);
    }

}
