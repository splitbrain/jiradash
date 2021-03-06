<?php

namespace splitbrain\JiraDash\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class HomeController
 *
 * Handles the home screen actions
 */
class HomeController extends BaseController
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
        return $this->view->render($response, 'home.twig', []);
    }

    /**
     * Add a new project
     *
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function addProject($request, $response)
    {
        $project = $request->getParam('add', '');
        $project = strtoupper($project);
        $project = preg_replace('/[^A-Z]+/', '', $project);

        if ($project) {
            try {
                $this->container->db->accessDB($project, true);
            } catch (\Exception $ignored) {
                // we have no error messaging in place, yet so we just let the user firgure it out themselves
            }
        }

        return $response->withRedirect($this->container->router->pathFor('home'));
    }


}
