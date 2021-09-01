<?php

namespace Oro\Bundle\NavigationBundle\Entity\Builder;

use Psr\Container\ContainerInterface;

/**
 * The navigation item factory.
 */
class ItemFactory
{
    /** @var ContainerInterface */
    private $builders;

    public function __construct(ContainerInterface $builders)
    {
        $this->builders = $builders;
    }

    /**
     * Creates a navigation item.
     *
     * @param string $type
     * @param array  $params
     *
     * @return object|null
     */
    public function createItem(string $type, $params)
    {
        if (!$this->builders->has($type)) {
            return null;
        }

        /** @var AbstractBuilder $builder */
        $builder = $this->builders->get($type);

        return $builder->buildItem($params);
    }

    /**
     * Gets a navigation item.
     *
     * @param string $type
     * @param int    $itemId
     *
     * @return object|null
     */
    public function findItem(string $type, $itemId)
    {
        if (!$this->builders->has($type)) {
            return null;
        }

        /** @var AbstractBuilder $builder */
        $builder = $this->builders->get($type);

        return $builder->findItem($itemId);
    }
}
