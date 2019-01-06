<?php

namespace splitbrain\JiraDash\Renderer;

class FlatHTML extends AbstractRenderer
{


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


    protected function renderHeaders($headers, $prefix = 0)
    {
        $doc = '<thead><tr>';
        $doc .= implode('', array_fill(0, $prefix, '<th>&nbsp;</th>'));
        foreach ($headers as $h) {
            $doc .= '<th>' . htmlspecialchars($this->formatHeader($h)) . '</th>';
        }
        $doc .= '</tr></thead>';

        return $doc;
    }

    protected function renderRow($row, $prefix = 0)
    {
        $doc = '<tr>';
        $doc .= implode('', array_fill(0, $prefix, '<td>&nbsp;</td>'));
        foreach ($row as $key => $val) {
            $doc .= '<td>';
            $doc .= $this->formatValue($key, $val);
            $doc .= '</td>';
        }
        $doc .= '</tr>';

        return $doc;
    }

    protected function formatValue($name, $value)
    {
        if($name === 'issue_id') return $this->formatIssueId($value);
        return htmlspecialchars(parent::formatValue($name, $value));
    }

    protected function formatIssueId($val)
    {
        $base = rtrim($this->container->settings['app']['api']['base'], '/');
        $url = "$base/browse/{$this->project}-$val";

        return '<a href="' . $url . '" target="_blank">' . $this->project . '-' . $val . '</a>';
    }
}
