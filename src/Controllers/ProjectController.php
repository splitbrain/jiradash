<?php

namespace splitbrain\JiraDash\Controllers;

use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use splitbrain\JiraDash\Renderer\AbstractRenderer;
use splitbrain\JiraDash\Utilities\ReportBuilder;

/**
 * Class ProjectController
 *
 * The main view to analyze a project
 */
class ProjectController extends BaseController
{
    /**
     * @var array the default report setup
     */
    protected $default = [
        'epics' => 0,
        'versions' => 0,
        'sprints' => 1,
        'issues' => 0,
        'userlogs' => 1,
        'worklogs' => 0,
        'renderer' => 'TreeHTML',
    ];


    /**
     * Execute queries and display reports
     *
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

        // parameters
        $sql = $request->getParam('sql');
        $rc = $request->getParam('rc', $this->default);

        // use report builder when no custom SQL is given
        if (!$request->getParam('custom', false)) {
            $rb = ReportBuilder::fromConfig($db, $rc);
            $sql = $rb->getSQL();
        }

        // execute the query
        $error = '';
        $result = [];
        if ($sql) {
            try {
                $result = $db->queryAll($sql);
            } catch (\PDOException $e) {
                $error = $e->getMessage();
            }
        }

        // create the wanted renderer
        $rclass = '\\splitbrain\\JiraDash\\Renderer\\' . $rc['renderer'];
        /** @var AbstractRenderer $r */
        $r = new $rclass($this->container, $rc, $project);

        return $this->view->render($response, 'project.twig', [
            'title' => "Project $project",
            'project' => $project,
            'sql' => $sql,
            'rc' => $rc,
            'result' => $r->render($result),
            'error' => $error,
        ]);
    }
}
