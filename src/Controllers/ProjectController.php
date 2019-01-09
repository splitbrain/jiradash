<?php

namespace splitbrain\JiraDash\Controllers;

use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use splitbrain\JiraDash\Renderer\AbstractRenderer;
use splitbrain\JiraDash\Utilities\ReportBuilder;
use splitbrain\JiraDash\Utilities\SqlHelper;

/**
 * Class HomeController
 * @package CosmoCode\PMIDashboard\Controllers
 */
class ProjectController extends BaseController
{
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

        $error = '';
        $result = [];
        if ($sql) {
            try {
                $result = $db->queryAll($sql);
            } catch (\PDOException $e) {
                $error = $e->getMessage();
            }
        }

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


    protected function runSQL(SqlHelper $db, $sql)
    {

        $result = $db->queryAll($sql);

        return $result;
    }

}
