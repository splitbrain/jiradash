<?php

namespace splitbrain\JiraDash\CLI;

use splitbrain\JiraDash\Service\JiraAPI;
use splitbrain\JiraDash\Service\TempoAPI;
use splitbrain\JiraDash\Utilities\SqlHelper;
use splitbrain\phpcli\Exception;

class Update extends AbstractCLI
{
    /** @var JiraAPI */
    protected $jiraAPI;
    /** @var TempoAPI */
    protected $tempoAPI;
    /** @var SqlHelper */
    protected $db;

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
     * @throws \Exception
     */
    protected function main(\splitbrain\phpcli\Options $options)
    {
        $this->jiraAPI = new \splitbrain\JiraDash\Service\JiraAPI(
            $this->container->settings['app']['api']['user'],
            $this->container->settings['app']['api']['pass'],
            $this->container->settings['app']['api']['base']
        );

        $this->tempoAPI = new TempoAPI(
            $this->container->settings['app']['tempo']['token']
        );

        $args = $options->getArgs();
        $project = $args[0];


        $this->db = $this->container->db->accessDB($project, true);

        $this->importProject($project);
        $this->importTimeSheetLogs($project);
    }

    /**
     * @param string $project
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    protected function importProject($project)
    {
        $this->info('Importing Tickets...');
        $issues = $this->jiraAPI->queryJQL('/rest/api/latest/search/', "project = $project");
        #print_r($issues);

        $this->db->begin();
        foreach ($issues['issues'] as $issue) {
            $this->info($issue['key']);

            // handle sprint
            $sprint = $this->findSprint($issue['fields']);
            if ($sprint) {
                $insert = [
                    'id' => $sprint['id'],
                    'title' => $sprint['name'],
                    'description' => $sprint['goal'],
                    'created' => $this->dateClean($sprint['startDate'])
                ];
                $this->db->insertRecord('sprint', $insert);
            }

            if ($issue['fields']['issuetype']['name'] === 'Epic') {
                // epic issue
                $insert = [
                    'id' => preg_replace('/\D+/', '', $issue['key']),
                    'title' => $issue['fields'][$this->container->settings['app']['fields']['epic_title']],
                    'description' => $issue['fields']['summary'],
                    'created' => $this->dateClean($issue['fields']['created']),
                ];
                $this->db->insertRecord('epic', $insert);
            } else {
                // normal issue
                $insert = [
                    'id' => preg_replace('/\D+/', '', $issue['key']),
                    'sprint_id' => $sprint ? $sprint['id'] : null,
                    'epic_id' => preg_replace('/\D+/', '', $issue['fields'][$this->container->settings['app']['fields']['epic_link']]),
                    'title' => $issue['fields']['summary'],
                    'description' => $issue['fields']['description'],
                    'estimate' => (int)$issue['fields']['aggregatetimeoriginalestimate'],
                    'logged' => (int)$issue['fields']['aggregatetimespent'],
                    'type' => $issue['fields']['issuetype']['name'],
                    'user' => $issue['fields']['assignee']['displayName'],
                    'status' => $issue['fields']['status']['name'],
                    'created' => $this->dateClean($issue['fields']['created']),
                    'updated' => $this->dateClean($issue['fields']['updated']),
                    'prio' => $issue['fields']['priority']['name'],
                ];
                $this->db->insertRecord('issue', $insert);

                #$this->importWorklogs($issue['key']);
            }
        }
        $this->db->commit();
    }

    /**
     * Cleans a Jira data into a SQLite compatible datetime
     *
     * @param $string
     * @return string
     */
    protected function dateClean($string)
    {
        $ts = strtotime($string);
        if (!$ts) return '';
        return strftime('%Y-%m-%d %H:%M:%S', $ts);
    }

    /**
     * Import worklogs
     *
     * @param string $project
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function importTimeSheetLogs($project)
    {
        $this->info('Importing worklogs...');
        $got = 0;
        $logs = $this->tempoAPI->query("/2/worklogs/project/$project", []);
        $this->db->begin();
        foreach ($logs['results'] as $log) {
            $insert = [
                'id' => $log['jiraWorklogId'],
                'issue_id' => preg_replace('/\D+/', '', $log['issue']['key']),
                'created' => $this->dateClean($log['startDate']),
                'logged' => $log['timeSpentSeconds'],
                'user' => $log['author']['displayName'],
                'description' => $log['description'],
            ];
            try {
                $this->db->insertRecord('worklog', $insert);
                $got++;
            } catch (\Exception $e) {
                $this->debug(print_r($insert, true));
                $this->error('failed to insert worklog for issue {issue}', ['issue' => $insert['id']]);
                continue;
            }
        }
        $this->db->commit();
        if ($got > 0) $this->success('Imported {count} logs', ['count' => $got]);
        #print_r($logs);
    }

    /**
     * Imports worklogs from Jira
     *
     * @deprecated we use the tempo API instead
     * @param string $key issue ID
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function importWorklogs($key)
    {
        $logs = $this->jiraAPI->query('/rest/api/2/issue/' . $key . '/worklog', []);
        foreach ($logs['worklogs'] as $log) {
            $insert = [
                'id' => $log['id'],
                'issue_id' => preg_replace('/\D+/', '', $key),
                'created' => $this->dateClean($log['started']),
                'logged' => $log['timeSpentSeconds'],
                'user' => $log['author']['displayName'],
                'description' => isset($log['comment']) ? $log['comment'] : '',
            ];
            $this->db->insertRecord('worklog', $insert);
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
