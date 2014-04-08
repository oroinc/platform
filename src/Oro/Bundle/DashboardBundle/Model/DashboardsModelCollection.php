<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;

class DashboardsModelCollection implements \Iterator, \Countable
{
    /**
     * @var DashboardModel[]
     */
    protected $dashboards;

    /**
     * @var int cursor position
     */
    protected $position = 0;

    /**
     * @param DashboardModel[] $dashboards
     */
    public function __construct(array $dashboards)
    {
        $this->dashboards  = $dashboards;
    }

    /**
     * @param string $name
     *
     * @return null|DashboardModel
     */
    public function getByName($name)
    {
        return $this->findByCriteria('name', $name);
    }

    /**
     * @param $id
     *
     * @return null|DashboardModel
     */
    public function getById($id)
    {
        return $this->findByCriteria('id', $id);
    }

    /**
     * @param $name
     * @param $value
     *
     * @throws \Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException
     *
     * @return null|DashboardModel
     */
    protected function findByCriteria($name, $value)
    {
        $getter = 'get' . ucfirst($name);

        if (!method_exists('\Oro\Bundle\DashboardBundle\Entity\Dashboard', $getter)) {
            throw new InvalidArgumentException("getter for property {$name} not found");
        }

        foreach ($this->dashboards as $dashboard) {
            if ($dashboard->getDashboard()->$getter() == $value) {
                return $dashboard;
            }
        }

        return null;
    }

    /**
     * return DashboardModel
     */
    public function current()
    {
        return $this->dashboards[$this->position];
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
        return isset($this->dashboards[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->dashboards);
    }
}
