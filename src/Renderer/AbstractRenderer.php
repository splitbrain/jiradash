<?php

namespace splitbrain\JiraDash\Renderer;

use splitbrain\JiraDash\Container;

/**
 * Class AbstractRenderer
 *
 * This is what a result renderer looks like
 */
abstract class AbstractRenderer
{
    /** @var Container */
    protected $container;
    /** @var string The project key this rendered result is for */
    protected $project;
    /** @var array The report configuration that was used when creating the result */
    protected $rc;

    /** @var array Nicer column names when displaying */
    protected $columnNames = [
        'epic_title' => 'Epic',
        'epic_estimate' => 'Epic Est.',
        'epic_offer' => 'Epic Offer',
        'version_title' => 'Version',
        'version_estimate' => 'Vers. Est.',
        'version_offer' => 'Vers. Offer',
        'sprint_title' => 'Sprint',
        'sprint_estimate' => 'Sprint Est.',
        'sprint_offer' => 'Sprint Offer',
        'issue_id' => 'ID',
        'issue_type' => 'Type',
        'issue_title' => 'Title',
        'issue_estimate' => 'Issue Est.',
        'worklog_user' => 'User',
        'worklog_created' => 'Log Date',
        'worklog_logged' => 'Logged',
        'worklog_description' => 'Worklog',
    ];

    /**
     * AbstractRenderer constructor.
     *
     * @param Container $container
     * @param array $rc
     * @param string $project
     */
    public function __construct(Container $container, $rc, $project)
    {
        $this->container = $container;
        $this->rc = $rc;
        $this->project = $project;
    }

    /**
     * Render the given data
     *
     * @param array $data The result of the SQL query that should be rendered
     * @return string the output
     */
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

    /**
     * Format the given value according to the column name
     *
     * Tries to find a formatColumnType() method, then falls back to a formatColumn() method. If both
     * aren't available, the value is returned as is.
     *
     * snake_case is converted to CamelCase when looking up the methods.
     *
     * Inheriting classes should apply this function to each cell. Additional escaping may need to be
     * done.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
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

    /**
     * Formats logged work times
     *
     * Converts seconds to days or hours according to RC configuration
     *
     * @param int $value
     * @return string
     */
    protected function formatLogged($value)
    {
        if (empty($this->rc['hours'])) {
            return round($value / (60 * 60 * 8), 2) . 'd';
        } else {
            return round($value / (60 * 60), 2) . 'h';
        }
    }

    /**
     * Alias for formatLogged()
     *
     * @param int $value
     * @return string
     */
    protected function formatEstimate($value)
    {
        return $this->formatLogged($value);
    }

    /**
     * Alias for formatLogged()
     *
     * @param int $value
     * @return string
     */
    protected function formatOffer($value)
    {
        return $this->formatLogged($value);
    }

    /**
     * Reformat date times to only show the date
     *
     * @param string $value
     * @return string
     */
    protected function formatCreated($value)
    {
        if (!$value) return '';
        return date('Y-m-d', strtotime($value));
    }
}
