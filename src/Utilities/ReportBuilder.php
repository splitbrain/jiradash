<?php

namespace splitbrain\JiraDash\Utilities;

class ReportBuilder {

    protected $columns = [];
    protected $groups = [];


    public function showIssues() {
        $this->columns['i.id'] = 'issue_id';
        $this->columns['i.title'] = 'issue_title';
        $this->columns['s.title'] = 'sprint_title';
        $this->columns['e.title'] = 'epic_title';

        $this->groups[] = 'i.id';
    }

    public function showSprints() {
        $this->columns['s.title'] = 'sprint_title';

        $this->groups[] = 's.id';
    }

    public function showEpics() {
        $this->columns['e.title'] = 'epic_title';

        $this->groups[] = 'e.id';
    }

    public function showUserLogs() {
        $this->columns['w.user'] = 'worklog_user';

        $this->groups[] = 'w.user';
    }

    public function showWorkLogs() {
        $this->columns['w.user'] = 'worklog_user';
        $this->columns['w.created'] = 'worklog_created';

        $this->groups[] = 'w.id';
    }

    public function getSQL()
    {
        // select columns
        $sql = 'SELECT ';
        foreach ($this->columns as $c => $a){
            $sql .= "$c AS $a,";
        }
        $sql .= "SUM(w.logged) AS worklog_logged\n";

        // from tables
        $sql .= "FROM issue AS i\n";
        $sql .= "LEFT JOIN epic AS e ON i.epic_id = e.id\n";
        $sql .= "LEFT JOIN sprint AS s ON i.sprint_id = s.id\n";
        $sql .= "LEFT JOIN worklog w on i.id = w.issue_id\n";

        // FIXME add filters here

        // group by
        $sql .= 'GROUP BY '.implode(', ', $this->groups);

        return $sql;
    }
}
