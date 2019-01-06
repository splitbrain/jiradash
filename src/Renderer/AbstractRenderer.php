<?php


namespace splitbrain\JiraDash\Renderer;

use splitbrain\JiraDash\Container;

abstract class AbstractRenderer
{
    protected $container;
    protected $project;
    protected $rc;

    protected $columnNames = [
        'epic_title' => 'Epic',
        'sprint_title' => 'Sprint',
        'issue_id' => 'ID',
        'issue_type' => 'Type',
        'issue_title' => 'Title',
        'worklog_user' => 'User',
        'worklog_created' => 'Log Date',
        'worklog_logged' => 'Logged',
    ];

    public function __construct(Container $container, $rc, $project)
    {
        $this->container = $container;
        $this->rc = $rc;
        $this->project = $project;
    }

    abstract public function render($data);

    /**
     * Returns a human readable column name
     *
     * @param string $in
     * @return string
     */
    protected function formatHeader($in)
    {
        if (isset($this->columnNames[$in])) return $this->columnNames[$in];
        return $in;
    }

    protected function formatValue($name, $value)
    {
        $camel = str_replace('_', '', ucwords($name, '_'));
        $formatter = "format$camel";
        if (is_callable([$this, $formatter])) {
            return $this->$formatter($value, $name);
        }

        $post = explode('_', $name);
        $post = ucfirst(array_pop($post));
        $formatter = "format$post";
        if (is_callable([$this, $formatter])) {
            return $this->$formatter($value, $name);
        }

        return $value;
    }

    protected function formatLogged($value)
    {
        return round($value / (60 * 60 * 5), 2) . 'd';
    }

    protected function formatEstimate($value)
    {
        return $this->formatLogged($value);
    }
}
