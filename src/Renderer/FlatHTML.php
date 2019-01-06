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


    protected function renderHeaders($headers)
    {
        $doc = '<thead><tr>';
        foreach ($headers as $h) {
            $doc .= '<th>' . htmlspecialchars($this->formatHeader($h)) . '</th>';
        }
        $doc .= '</tr></thead>';

        return $doc;
    }

    protected function renderRow($row)
    {
        $doc = '<tr>';
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
        $camel = str_replace('_', '', ucwords($name, '_'));
        $formatter = "format$camel";
        if (is_callable([$this, $formatter])) {
            return $this->$formatter($value, $name);
        }
        return htmlspecialchars($value);
    }

    protected function formatIssueId($val)
    {
        $base = rtrim($this->container->settings['app']['api']['base'], '/');
        $url = "$base/browse/{$this->project}-$val";

        return '<a href="' . $url . '" target="_blank">' . $this->project . '-' . $val . '</a>';
    }
}
