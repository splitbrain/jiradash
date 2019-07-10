<?php

namespace splitbrain\JiraDash\Renderer;

use splitbrain\JiraDash\Container;

/**
 * Class TreeHTML
 *
 * Render results as a nested table with summarized headers
 */
class TreeHTML extends FlatHTML
{
    /** @var array categorization to be used */
    protected $categories;

    /** @inheritdoc */
    public function __construct(Container $container, $rc, $project)
    {
        parent::__construct($container, $rc, $project);

        // what are our categories?
        $vals = [];
        foreach (['epics', 'versions', 'sprints', 'issues', 'userlogs', 'worklogs'] as $flag) {
            if (!empty($rc[$flag])) $vals[$flag] = 1;
        }
        // do not use epics and versions at the same time
        if (isset($vals['epics']) && isset($vals['versions'])) unset($vals['versions']);
        array_pop($vals); // last one is the smallest resolution, not a category
        $this->categories = $vals;
    }

    /**
     * Turn the flat array into a tree structure with added meta data and sums,
     * then render that structure
     *
     * @todo split into smaller chunks
     * @inheritdoc
     */
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
                    $tree['sub'][$epic]['estimate'] = $row['epic_estimate'];
                    $tree['sub'][$epic]['offer'] = $row['epic_offer'];
                } else {
                    $tree['sub'][$epic]['name'] = '<i>No Epic</i>';
                }

                unset($row['epic_title']);
                unset($row['epic_estimate']);
                unset($row['epic_offer']);
            } elseif (isset($this->categories['versions'])) {
                // we handle versions like epics, but we only support either one!
                $epic = $row['version_title'];
                if ($epic) {
                    $tree['sub'][$epic]['name'] = 'Version "' . htmlspecialchars($row['version_title']) . '"';
                    $tree['sub'][$epic]['estimate'] = $row['version_estimate'];
                    $tree['sub'][$epic]['offer'] = $row['version_offer'];
                } else {
                    $tree['sub'][$epic]['name'] = '<i>No Version</i>';
                }

                unset($row['version_title']);
                unset($row['version_estimate']);
                unset($row['version_offer']);
            } else {
                $epic = '–';
            }

            if (isset($this->categories['sprints'])) {
                $sprint = $row['sprint_title'];
                if ($sprint) {
                    $tree['sub'][$epic]['sub'][$sprint]['name'] = 'Sprint "' . htmlspecialchars($row['sprint_title']) . '"';
                    $tree['sub'][$epic]['sub'][$sprint]['estimate'] = $row['sprint_estimate'];
                    $tree['sub'][$epic]['sub'][$sprint]['offer'] = $row['sprint_offer'];
                } else {
                    $tree['sub'][$epic]['sub'][$sprint]['name'] = '<i>No Sprint</i>';
                }

                unset($row['sprint_estimate']);
                unset($row['sprint_offer']);
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
                $tree['sub'][$epic]['sub'][$sprint]['sub'][$issue]['estimate'] = $row['issue_estimate'];

                unset($row['issue_estimate']);
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
        $doc = '<table class="table is-narrow is-hoverable is-fullwidth">';
        $doc .= parent::renderHeaders(array_keys($row), $cats);
        $doc .= $this->renderTree($tree, $cols + $cats, 0);
        $doc .= '</table>';

        return $doc;
    }

    /**
     * Renders the given tree structure
     *
     * Called recursively
     *
     * @todo split out color handling
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
                $doc .= '<th colspan="' . $span . '" class="has-background-white-ter">';
                $doc .= $item['name'];

                if (!empty($item['estimate'])) {
                    if ($item['estimate'] >= $item['log']) {
                        $color = 'success';
                    } else {
                        $color = 'danger';
                    }
                    $doc .= '<small class="is-pulled-right has-text-' . $color . '" style="padding: 0.1em 0.2em">';
                    $doc .= 'Estimate: ' . $this->formatValue('estimate', $item['estimate']);
                    $doc .= '</small> ';
                }

                if (!empty($item['offer'])) {
                    if ($item['offer'] >= $item['log']) {
                        $color = 'success';
                    } else {
                        $color = 'danger';
                    }
                    $doc .= '<small class="is-pulled-right has-text-' . $color . '" style="padding: 0.1em 0.2em">';
                    $doc .= 'Offer: ' . $this->formatValue('offer', $item['offer']);
                    $doc .= '</small> ';
                }

                $doc .= '</th>';
                $doc .= '<th class="has-background-white-ter">';
                $doc .= 'Σ' . $this->formatValue('logged', $item['log']);
                $doc .= '</th>';
                $doc .= '</tr>';

                $doc .= $this->renderTree($item, $cols, $level + 1);
            }

        } else {
            $doc .= $this->renderTree($tree['sub']['–'], $cols, $level);
        }

        return $doc;
    }

    /**
     * Render the actual non-category data as table rows
     *
     * Uses the parent renderer
     *
     * @param $data
     * @return string
     */
    protected function renderData($data)
    {
        $cats = count($this->categories);
        $doc = '';
        foreach ($data as $row) {
            if(! array_filter(array_values($row)) ) continue; // skip completely empty rows

            $doc .= parent::renderRow($row, $cats);
        }
        return $doc;
    }
}
