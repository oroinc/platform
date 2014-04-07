<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;

class WidgetsModelCollection implements \Iterator, \Countable
{
    /**
     * @var Dashboard
     */
    private $dashboard;

    /**
     * @var WidgetModel[]|null
     */
    private $widgetModels = null;

    private $position;

    /**
     * @var WidgetModelFactory
     */
    private $widgetModelFactory;

    public function __construct(Dashboard $dashboard, WidgetModelFactory $widgetModelFactory)
    {
        $this->dashboard = $dashboard;
        $this->widgetModelFactory = $widgetModelFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $elements = $this->getElements();
        return $elements[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        $elements = $this->getElements();
        return isset($elements[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    private function getElements()
    {
        if ($this->widgetModels === null) {
            $this->widgetModels = $this->widgetModelFactory->getModels($this->dashboard);
        }

        return $this->widgetModels;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        $elements = $this->getElements();
        return count($elements);
    }
}
