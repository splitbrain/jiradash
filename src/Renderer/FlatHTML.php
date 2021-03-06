<?php

namespace splitbrain\JiraDash\Renderer;

/**
 * Class FlatHTML
 *
 * Render results as a simple HTML table
 */
class FlatHTML extends AbstractRenderer
{
    // reference to the current row for value rendering
    protected $currentRow;

    /** @inheritdoc */
    public function render($data)
    {
        if (!$data) return '';

        $doc = '<table class="table is-striped is-narrow is-hoverable is-fullwidth">';
        $doc .= $this->renderHeaders(array_keys($data[0]));
        foreach ($data as $row) {
            $doc .= $this->renderRow($row);
        }
        $doc .= '</table>';

        return $doc;
    }

    /**
     * Renders the header columns
     *
     * @param array $headers
     * @param int $prefix number of empty cells to add in front
     * @return string
     */
    protected function renderHeaders($headers, $prefix = 0)
    {
        $doc = '<thead><tr>';
        $doc .= implode('', array_fill(0, $prefix, '<th>&nbsp;</th>'));
        foreach ($headers as $h) {
            $doc .= '<th title="' . htmlspecialchars($h) . '">';
            $doc .= htmlspecialchars($this->formatHeader($h));
            $doc .= '</th>';
        }
        $doc .= '</tr></thead>';

        return $doc;
    }

    /**
     * Renders a single row
     *
     * @param array $row
     * @param int $prefix number of empty cells to add in front
     * @return string
     */
    protected function renderRow($row, $prefix = 0)
    {
        $this->currentRow = $row;
        $doc = '<tr>';
        $doc .= implode('', array_fill(0, $prefix, '<td>&nbsp;</td>'));
        foreach ($row as $key => $val) {
            $doc .= '<td>';
            $doc .= $this->formatValue($key, $val);
            $doc .= '</td>';
        }
        $doc .= '</tr>';
        $this->currentRow = null;

        return $doc;
    }

    /**
     * We escape everything that is not handled by our own format methods
     *
     * @inheritdoc
     */
    protected function formatValue($name, $value)
    {
        if ($name === 'issue_id') return $this->formatIssueId($value);
        if (substr($name, -8) === 'estimate') return $this->formatEstimate($value);
        if (substr($name, -5) === 'offer') return $this->formatEstimate($value);
        return htmlspecialchars(parent::formatValue($name, $value));
    }

    /**
     * Links issue IDs to Jira
     *
     * @param int $val
     * @return string safe HTML
     */
    protected function formatIssueId($val)
    {
        $base = rtrim($this->container->settings['app']['api']['base'], '/');
        $url = "$base/browse/{$this->project}-$val";

        return '<a href="' . $url . '" target="_blank">' . $this->project . '-' . $val . '</a>';
    }

    /**
     * Compares the esitmate with the logged work in the same row, colors accordingly
     *
     * @inheritdoc
     * @return string safe HTML
     */
    protected function formatEstimate($value)
    {
        $formatted = parent::formatEstimate($value);

        if (!empty($this->currentRow['worklog_logged']) && !empty($value)) {
            if ($value >= $this->currentRow['worklog_logged']) {
                $class = 'has-text-success';
            } else {
                $class = 'has-text-danger';
            }
        } else {
            $class = '';
        }

        return "<span class=\"$class\">$formatted</span>";
    }


}
