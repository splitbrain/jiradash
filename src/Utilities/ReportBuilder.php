<?php

namespace splitbrain\JiraDash\Utilities;

class ReportBuilder
{
    /** @var SqlHelper */
    protected $db;

    /** @var array column => alias */
    protected $columns = [];
    /** @var array column[] */
    protected $groups = [];
    /** @var array prio => column */
    protected $orders = [];
    /** @var array */
    protected $wheres = [
#        'nozero' => 'w.logged > 0'  // make this an option
    ];

    public function __construct(SqlHelper $db)
    {
        $this->db = $db;
    }

    public static function fromConfig(SqlHelper $db, $conf): ReportBuilder
    {
        $rb = new ReportBuilder($db);

        // handle show flags
        foreach ($conf as $key => $val) {
            $flag = 'show' . ucfirst($key);
            if (is_callable([$rb, $flag]) && $val) $rb->$flag();
        }

        // handle dates
        if (!empty($conf['start'])) $rb->setStart($conf['start']);
        if (!empty($conf['end'])) $rb->setEnd($conf['end']);

        return $rb;
    }

    public function setStart($date)
    {
        $this->wheres['start'] = "DATE(w.created) >= DATE(" . $this->db->pdo()->quote($date) . ')';
    }

    public function setEnd($date)
    {
        $this->wheres['end'] = "DATE(w.created) <= DATE(" . $this->db->pdo()->quote($date) . ')';
    }

    public function showEpics()
    {
        $this->columns['e.title'] = 'epic_title';

        $this->groups[] = 'e.id';
        $this->orders[10] = 'e.title ASC';
    }

    public function showSprints()
    {
        $this->columns['s.title'] = 'sprint_title';

        $this->groups[] = 's.id';
        $this->orders[20] = 's.created DESC';
        $this->orders[21] = 's.title ASC';
    }

    public function showIssues()
    {
        $this->columns['i.id'] = 'issue_id';
        $this->columns['i.type'] = 'issue_type';
        $this->columns['i.title'] = 'issue_title';
        $this->columns['s.title'] = 'sprint_title';
        $this->columns['e.title'] = 'epic_title';

        $this->groups[] = 'i.id';
        $this->orders[30] = 'i.id DESC';
    }

    public function showUserlogs()
    {
        $this->columns['w.user'] = 'worklog_user';

        $this->groups[] = 'w.user';
        $this->orders[40] = 'w.user ASC';
    }

    public function showWorklogs()
    {
        $this->columns['w.user'] = 'worklog_user';
        $this->columns['w.created'] = 'worklog_created';

        $this->groups[] = 'w.id';
        $this->orders[50] = 'w.created DESC';
    }

    public function getSQL()
    {
        // select columns
        $sql = "SELECT \n";
        foreach ($this->columns as $c => $a) {
            $sql .= "$c AS $a,\n";
        }
        $sql .= "SUM(w.logged) AS worklog_logged\n";

        // from tables
        $sql .= "FROM issue AS i\n";
        $sql .= "LEFT JOIN epic AS e ON i.epic_id = e.id\n";
        $sql .= "LEFT JOIN sprint AS s ON i.sprint_id = s.id\n";
        $sql .= "LEFT JOIN worklog w on i.id = w.issue_id\n";

        // wheres
        if ($this->wheres) {
            $sql .= "WHERE (\n";
            $sql .= implode("\nAND ", $this->wheres);
            $sql .= "\n)\n";
        }

        // group by
        $sql .= 'GROUP BY ' . implode(', ', $this->groups) . "\n";

        // order by
        $sql .= 'ORDER BY ' . implode(', ', $this->orders) . "\n";

        return $sql;
    }
}
