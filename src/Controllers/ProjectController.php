<?php

namespace splitbrain\JiraDash\Controllers;

use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use splitbrain\JiraDash\Utilities\ReportBuilder;
use splitbrain\JiraDash\Utilities\SqlHelper;

/**
 * Class HomeController
 * @package CosmoCode\PMIDashboard\Controllers
 */
class ProjectController extends BaseController
{

    /**
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface
     * @throws NotFoundException
     */
    public function __invoke($request, $response)
    {
        $project = $request->getAttribute('project');
        try {
            $db = $this->container->db->accessDB($project);
        } catch (\Exception $e) {
            throw new NotFoundException($request, $response);
        }

        $error = '';
        $result = [];
        $sql = $request->getParam('sql');

        if(!$sql) {
            $rb = new ReportBuilder();
            $rb->showEpics();
            $rb->showIssues();
            $sql = $rb->getSQL();
        }


        if($sql) {
            try {
                $result = $db->queryAll($sql);
            } catch (\PDOException $e) {
                $error = $e->getMessage();
            }
        }


        return $this->view->render($response, 'project.twig', [
            'title' => "Project $project",
            'project' => $project,
            'sql' => $sql,
            'result' => $result,
            'error' => $error,
        ]);
    }


    protected function runSQL(SqlHelper $db, $sql) {

        $result = $db->queryAll($sql);

        return $result;
    }

}
