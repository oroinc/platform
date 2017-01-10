<?php

namespace Oro\Bundle\ActionBundle\Model\Criteria;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;

class OperationFindCriteria
{
    /** @var string */
    private $entityClass;

    /** @var string */
    private $route;

    /** @var string */
    private $datagrid;

    /** @var array */
    private $rawGroup;

    /** @var array|null */
    private $groups;

    /**
     * @param string $entityClass
     * @param string $route
     * @param string $datagrid
     * @param string|array|null $group
     */
    public function __construct($entityClass, $route, $datagrid, $group = null)
    {
        $this->entityClass = $entityClass;
        $this->route = $route;
        $this->datagrid = $datagrid;
        $this->rawGroup = $group === null ? ButtonInterface::DEFAULT_GROUP : $group;
    }

    /**
     * @param ButtonSearchContext $buttonSearchContext
     * @return static
     */
    public static function createFromButtonSearchContext(ButtonSearchContext $buttonSearchContext)
    {
        return new static(
            $buttonSearchContext->getEntityClass(),
            $buttonSearchContext->getRouteName(),
            $buttonSearchContext->getDatagrid(),
            $buttonSearchContext->getGroup()
        );
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function getDatagrid()
    {
        return $this->datagrid;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        //if not normalized yet
        if (null === $this->groups) {
            $this->groups = array_map(
                'strval',
                is_object($this->rawGroup) ? [$this->rawGroup] : (array)$this->rawGroup
            );
        }

        return $this->groups;
    }
}
