<?php

namespace Oro\Bundle\NavigationBundle\Entity\Builder;

class ItemFactory
{
    /**
     * Collection of builders grouped by alias
     *
     * @var array
     */
    protected $builders = [];

    /**
     * Add builder
     *
     * @param AbstractBuilder $builder
     * @param string $groupName
     */
    public function addBuilder(AbstractBuilder $builder, $groupName = '')
    {
        $this->builders[$groupName][$builder->getType()] = $builder;
    }

    /**
     * Create navigation item
     *
     * @param  string      $type
     * @param  array       $params
     * @param  string      $groupName
     * @return null|object
     */
    public function createItem($type, $params, $groupName = '')
    {
        $item = null;

        try {
            $item = $this->getBuilder($type, $groupName)->buildItem($params);
        } catch (\Exception $e) {
        }

        return $item;
    }

    /**
     * Get navigation item
     *
     * @param  string      $type
     * @param  int         $itemId
     * @param  string      $groupName
     * @return null|object
     */
    public function findItem($type, $itemId, $groupName = '')
    {
        $item = null;

        try {
            $item = $this->getBuilder($type, $groupName)->findItem($itemId);
        } catch (\Exception $e) {
        }

        return $item;
    }

    /**
     * @param string $type
     * @param string $groupName
     * @return AbstractBuilder
     */
    protected function getBuilder($type, $groupName = '')
    {
        if (!isset($this->builders[$groupName][$type])) {
            throw new \UnexpectedValueException(
                sprintf('Builder with groupName `%s` and type `%s` not registered', $groupName, $type)
            );
        }

        return $this->builders[$groupName][$type];
    }
}
