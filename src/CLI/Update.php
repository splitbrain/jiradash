<?php

namespace splitbrain\JiraDash\CLI;

use splitbrain\JiraDash\Service\JiraAPI;
use splitbrain\JiraDash\Utilities\SqlHelper;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Exception;

class Update extends CLI
{
    /** @var JiraAPI */
    protected $client;

    /**
     * Register options and arguments on the given $options object
     *
     * @param \splitbrain\phpcli\Options $options
     * @return void
     *
     * @throws \splitbrain\phpcli\Exception
     */
    protected function setup(\splitbrain\phpcli\Options $options)
    {
        $options->setHelp('update data');
        $options->registerArgument('project', 'The project shortcut key');

    }

    /**
     * Your main program
     *
     * Arguments and options have been parsed when this is run
     *
     * @param \splitbrain\phpcli\Options $options
     * @return void
     *
     * @throws \splitbrain\phpcli\Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function main(\splitbrain\phpcli\Options $options)
    {
        $conf = json_decode(file_get_contents(__DIR__ . '/../../conf.json'), true);

        $this->client = new \splitbrain\JiraDash\Service\JiraAPI(
            $conf['jira_user'],
            $conf['jira_pass'],
            $conf['jira_base']
        );

        $args = $options->getArgs();
        $this->importProject($args[0]);
    }

    /**
     * @param string $project
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function importProject($project)
    {


        $issues = $this->client->queryJQL('/rest/api/latest/search/', "project = $project");
        #print_r($issues);

        $db = $this->openDB($project);
        $db->begin();
        foreach ($issues['issues'] as $issue) {
            $this->info($issue['key']);

            // handle sprint
            $sprint = $this->findSprint($issue['fields']);
            if ($sprint) {
                $insert = [
                    'id' => $sprint['id'],
                    'title' => $sprint['name'],
                    'description' => $sprint['goal'],
                    'created' => $sprint['startDate']
                ];
                $db->insertRecord('sprint', $insert);
            }

            if ($issue['fields']['issuetype']['name'] === 'Epic') {
                // epic issue
                $insert = [
                    'id' => preg_replace('/\D+/', '', $issue['key']),
                    'title' => $issue['fields']['customfield_10601'], #FIXME config
                    'description' => $issue['fields']['summary'],
                    'created' => $issue['fields']['created'],
                ];
                $db->insertRecord('epic', $insert);
            } else {
                // normal issue
                $insert = [
                    'id' => preg_replace('/\D+/', '', $issue['key']),
                    'sprint_id' => $sprint ? $sprint['id'] : null,
                    'epic_id' => preg_replace('/\D+/', '', $issue['fields']['customfield_10600']), #FIXME config
                    'title' => $issue['fields']['summary'],
                    'description' => $issue['fields']['description'],
                    'estimate' => (int)$issue['fields']['aggregatetimeoriginalestimate'],
                    'logged' => (int)$issue['fields']['aggregatetimespent'],
                    'type' => $issue['fields']['issuetype']['name'],
                    'user' => $issue['fields']['assignee']['displayName'],
                    'status' => $issue['fields']['status']['name'],
                    'created' => $issue['fields']['created'],
                    'updated' => $issue['fields']['updated'],
                    'prio' => $issue['fields']['priority']['name'],
                ];
                $db->insertRecord('issue', $insert);

                $this->importWorklogs($db, $issue['key']);
            }
        }
        $db->commit();
    }

    protected function importWorklogs(SqlHelper $db, $key) {
        $logs = $this->client->query('/rest/api/2/issue/'.$key.'/worklog',[]);
        foreach($logs['worklogs'] as $log) {
            $insert = [
                'id' => $log['id'],
                'issue_id' => preg_replace('/\D+/', '', $key),
                'created' => $log['started'],
                'logged' => $log['timeSpentSeconds'],
                'user' => $log['author']['displayName'],
                'description' => isset($log['comment']) ? $log['comment'] : '',
            ];
            $db->insertRecord('worklog', $insert);
        }
    }

    protected function openDB($project)
    {
        $dbdir = __DIR__ . '/../../data/';
        $dbfile = $dbdir . $project . '.sqlite';
        if (!file_exists($dbfile)) {
            throw new Exception('no database file and migrations not in place FIXME');
        }

        $pdo = new \PDO('sqlite:' . $dbfile);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return new SqlHelper($pdo);
    }

    /**
     * Find sprint info in list of fields
     *
     * @todo move to jiraapi class?
     * @param $fields
     * @return array
     */
    protected function findSprint($fields)
    {
        $sprint = [];
        foreach ($fields as $key => $val) {
            if (strpos($key, 'customfield_') !== 0) continue;
            if (!is_array($val) || !isset($val[0]) || !is_string($val[0])) continue;
            if (!preg_match('/^com.atlassian.greenhopper.service.sprint.Sprint/', $val[0])) continue;
            $data = explode('[', substr($val[0], 0, -1));
            $data = explode(',', $data[1]);
            foreach ($data as $line) {
                list($k, $v) = explode('=', $line);
                $sprint[$k] = $v;
            }
            break;
        }
        return $sprint;
    }
}
