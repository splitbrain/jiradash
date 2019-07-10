<?php

namespace splitbrain\JiraDash\CLI;

use splitbrain\JiraDash\Service\JiraAPI;
use splitbrain\JiraDash\Service\TempoAPI;
use splitbrain\JiraDash\Utilities\SqlHelper;

/**
 * Class Update
 *
 * Command Line Tool to update all project data
 */
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
     * @inheritdoc
     * @throws \splitbrain\phpcli\Exception
     */
    protected function setup(\splitbrain\phpcli\Options $options)
    {
        $options->setHelp('Update project data by fetching it from the APIs into SQLite databases.');
        $options->registerArgument('projects...', 'The project shortcut keys. Leave empty to update all.', false);
    }

    /**
     * Main program
     *
     * @inheritdoc
     * @throws \splitbrain\phpcli\Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    protected function main(\splitbrain\phpcli\Options $options)
    {
        $this->jiraAPI = new JiraAPI(
            $this->container->settings['app']['api']['user'],
            $this->container->settings['app']['api']['pass'],
            $this->container->settings['app']['api']['base']
        );

        $this->tempoAPI = new TempoAPI(
            $this->container->settings['app']['tempo']['token']
        );

        $projects = $options->getArgs();
        if (!count($projects)) $projects = array_keys($this->container->db->getProjects());

        foreach ($projects as $project) {
            $this->info("Updating $project...");
            try {
                $this->db = $this->container->db->accessDB($project, true);
                $this->importProject($project);
                $this->importTimeSheetLogs($project);
                $this->aggregateEstimates();
                $this->success("Updated $project");
            } catch (\Exception $e) {
                $this->debug($e->getTraceAsString());
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * Imports issues via Jira REST API for a given project key
     *
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

            // handle versions
            $version = null;
            if (isset($issue['fields']['fixVersions'][0])) {
                $version = $issue['fields']['fixVersions'][0];
                $insert = [
                    'id' => $version['id'],
                    'title' => $version['name'],
                    'description' => $version['description'],
                    'offer' => self::parseOfferEstimate($version['description']),
                ];
                $this->db->insertRecord('version', $insert);
            }

            // handle sprint
            $sprint = $this->findSprint($issue['fields']);
            if ($sprint) {
                $insert = [
                    'id' => $sprint['id'],
                    'title' => $sprint['name'],
                    'description' => $sprint['goal'] ?? '',
                    'created' => $this->dateClean($sprint['startDate'] ?? ''),
                    'offer' => self::parseOfferEstimate($sprint['goal'] ?? ''),
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
                    'offer' => (int)$issue['fields']['aggregatetimeoriginalestimate'],
                ];
                $this->db->insertRecord('epic', $insert);
            } else {
                // normal issue
                $insert = [
                    'id' => preg_replace('/\D+/', '', $issue['key']),
                    'sprint_id' => $sprint ? $sprint['id'] : null,
                    'epic_id' => preg_replace('/\D+/', '', $issue['fields'][$this->container->settings['app']['fields']['epic_link']]),
                    'version_id' => $version ? $version['id'] : null,
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
     * Cleans a Jira date data into a SQLite compatible datetime
     *
     * @param string $string Original Jira Date
     * @return string Cleaned Date
     */
    protected function dateClean($string)
    {
        $ts = strtotime($string);
        if (!$ts) return '';
        return strftime('%Y-%m-%d %H:%M:%S', $ts);
    }

    /**
     * Parse estimates from descriptions
     *
     * @param string $string the Description to parse
     * @return int the estimate in seconds (0 if no estimate found)
     */
    public static function parseOfferEstimate($string)
    {
        if (preg_match('/est(?:imated?)?: ?([\d\.,]+)([dhm])/i', $string, $m)) {
            $val = floatval(str_replace(',', '.', $m[1]));
            $unit = strtolower($m[2]);

            // convert to seconds
            if ($unit === 'd') {
                $val = $val * 60 * 60 * 8;
            } elseif ($unit === 'h') {
                $val = $val * 60 * 60;
            } else {
                $val = $val * 60;
            }

            return (int)$val;
        }

        return 0;
    }

    /**
     * Imports worklogs from the Tempo API for the given project
     *
     * @param string $project
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function importTimeSheetLogs($project)
    {
        $this->info('Importing worklogs...');
        $got = 0;
        $logs = $this->tempoAPI->query("/core/3/worklogs/project/$project", []);
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
                $this->error('failed to insert worklog for issue {issue} {msg}',
                    ['issue' => $insert['issue_id'], 'msg' => $e->getMessage()]
                );
                continue;
            }
        }
        $this->db->commit();
        if ($got > 0) $this->success('Imported {count} logs', ['count' => $got]);
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

    /**
     * Copy estimate aggregations into epics and sprints
     */
    protected function aggregateEstimates()
    {
        $sql = 'WITH a AS(
                    SELECT sprint_id, SUM(i.estimate) as estimate
                      FROM issue AS i
                  GROUP BY sprint_id
                )
                UPDATE sprint
                    SET estimate = (SELECT estimate FROM a WHERE a.sprint_id = sprint.id)';
        $this->db->exec($sql);

        $sql = 'WITH a AS(
                    SELECT epic_id, SUM(i.estimate) as estimate
                      FROM issue AS i
                  GROUP BY epic_id
                )
                UPDATE epic
                    SET estimate = (SELECT estimate FROM a WHERE a.epic_id = epic.id)';
        $this->db->exec($sql);

        $sql = 'WITH a AS(
                    SELECT version_id, SUM(i.estimate) as estimate
                      FROM issue AS i
                  GROUP BY version_id
                )
                UPDATE version
                    SET estimate = (SELECT estimate FROM a WHERE a.version_id = version.id)';
        $this->db->exec($sql);
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
