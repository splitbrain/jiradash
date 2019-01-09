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
        'epic_estimate' => 'Epic Est.',
        'sprint_title' => 'Sprint',
        'sprint_estimate' => 'Sprint Est.',
        'issue_id' => 'ID',
        'issue_type' => 'Type',
        'issue_title' => 'Title',
        'issue_estimate' => 'Issue Est.',
        'worklog_user' => 'User',
        'worklog_created' => 'Log Date',
        'worklog_logged' => 'Logged',
        'worklog_description' => 'Worklog',
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
        if(empty($this->rc['hours'])) {
            return round($value / (60 * 60 * 8), 2) . 'd';
        } else {
            return round($value / (60 * 60), 2) . 'h';
        }
    }

    protected function formatEstimate($value)
    {
        return $this->formatLogged($value);
    }

    protected function formatCreated($value)
    {
        if(!$value) return '';
        return date('Y-m-d', strtotime($value));
    }
}
