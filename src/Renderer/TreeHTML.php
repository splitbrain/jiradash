<?php

namespace splitbrain\JiraDash\Renderer;

use splitbrain\JiraDash\Container;

class TreeHTML extends FlatHTML
{

    protected $categories;

    public function __construct(Container $container, $rc, $project)
    {
        parent::__construct($container, $rc, $project);

        // what do we are our categories?
        $vals = [];
        foreach (['epics', 'sprints', 'issues', 'userlogs', 'worklogs'] as $flag) {
            if (!empty($rc[$flag])) $vals[$flag] = 1;
        }
        array_pop($vals); // last one is the smallest resolution, not a category
        $this->categories = $vals;
    }


    public function render($data)
    {
        if (!$data) return '';

        $tree = [];
        $cols = 0;
        foreach ($data as $row) {
            /*
              prepare identifiers for the selected categories
              headlines are prepared here as well
              duplicate fields are removed from the data row then
            */
            if (isset($this->categories['epics'])) {
                $epic = $row['epic_title'];
                if ($epic) {
                    $tree['sub'][$epic]['name'] = 'Epic "' . htmlspecialchars($row['epic_title']) . '"';
                } else {
                    $tree['sub'][$epic]['name'] = '<i>No Epic</i>';
                }

                unset($row['epic_title']);
            } else {
                $epic = '–';
            }

            if (isset($this->categories['sprints'])) {
                $sprint = $row['sprint_title'];
                if ($sprint) {
                    $tree['sub'][$epic]['sub'][$sprint]['name'] = 'Sprint "' . htmlspecialchars($row['sprint_title']) . '"';
                } else {
                    $tree['sub'][$epic]['sub'][$sprint]['name'] = '<i>No Sprint</i>';
                }

                unset($row['sprint_title']);
            } else {
                $sprint = '–';
            }

            if (isset($this->categories['issues'])) {
                $issue = 'issue' . $row['issue_id']; // keep sorting
                $tree['sub'][$epic]['sub'][$sprint]['sub'][$issue]['name'] =
                    $this->formatIssueId($row['issue_id']) . ' ' .
                    htmlspecialchars($row['issue_title'] .
                        ' (' . $row['issue_type'] . ')');

                unset($row['issue_id']);
                unset($row['issue_title']);
                unset($row['issue_type']);
            } else {
                $issue = '–';
            }

            if (isset($this->categories['userlogs'])) {
                $user = $row['worklog_user'];
                if ($user) {
                    $tree['sub'][$epic]['sub'][$sprint]['sub'][$issue]['sub'][$user]['name'] =
                        'User ' . htmlspecialchars($row['worklog_user']);
                } else {
                    $tree['sub'][$epic]['sub'][$sprint]['sub'][$issue]['sub'][$user]['name'] = '<i>No User</i>';
                }

                unset($row['worklog_user']);
            } else {
                $user = '–';
            }

            // we build a deep tree here using the above identifiers
            $tree['sub'][$epic]['sub'][$sprint]['sub'][$issue]['sub'][$user]['data'][] = $row;
            // we also count up, to avoid notices about uninitialized zero vals, we suppress errors @todo
            @$tree['sub'][$epic]['sub'][$sprint]['sub'][$issue]['sub'][$user]['log'] += $row['worklog_logged'];
            @$tree['sub'][$epic]['sub'][$sprint]['sub'][$issue]['log'] += $row['worklog_logged'];
            @$tree['sub'][$epic]['sub'][$sprint]['log'] += $row['worklog_logged'];
            @$tree['sub'][$epic]['log'] += $row['worklog_logged'];

            // remember the number of columns for the table rendereing
            if (!$cols) $cols = count($row);
        }

        $cats = count($this->categories);
        $doc = '<table class="table is-striped is-narrow is-hoverable is-fullwidth">';
        $doc .= parent::renderHeaders(array_keys($row), $cats);
        $doc .= $this->renderTree($tree, $cols + $cats, 0);
        $doc .= '</table>';

        return $doc;
    }

    /**
     * @param array $tree the (sub) tree to render
     * @param int $cols the columns in the data (including prefix)
     * @param int $level the recursion level
     * @return string
     */
    protected function renderTree($tree, $cols, $level)
    {
        if (isset($tree['data'])) return $this->renderData($tree['data']);

        $doc = '';
        if (count($tree['sub']) > 1 || !isset($tree['sub']['–'])) {
            $span = $cols - $level - 1;

            foreach ($tree['sub'] as $name => $item) {
                $doc .= '<tr>';
                $doc .= implode('', array_fill(0, $level, '<th>&nbsp;</th>'));
                $doc .= '<th colspan="' . $span . '">';
                $doc .= $item['name'];
                $doc .= '</th>';
                $doc .= '<th>';
                $doc .= 'Σ'.$this->formatValue('logged', $item['log']);
                $doc .= '</th>';
                $doc .= '</tr>';

                $doc .= $this->renderTree($item, $cols, $level + 1);
            }

        } else {
            $doc .= $this->renderTree($tree['sub']['–'], $cols, $level);
        }

        return $doc;
    }

    protected function renderData($data)
    {
        $cats = count($this->categories);
        $doc = '';
        foreach ($data as $row) {
            $doc .= parent::renderRow($row, $cats);
        }
        return $doc;
    }
}