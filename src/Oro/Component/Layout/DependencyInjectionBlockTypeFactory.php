<?php

namespace Oro\Component\Layout;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\Layout\BlockTypeFactoryInterface;

class DependencyInjectionBlockTypeFactory implements BlockTypeFactoryInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $typeServiceIds;

    public function __construct(ContainerInterface $container, array $typeServiceIds)
    {
        $this->container = $container;
        $this->typeServiceIds = $typeServiceIds;
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockType($name)
    {
        if (!isset($this->typeServiceIds[$name])) {
            return null;
        }

        $type = $this->container->get($this->typeServiceIds[$name]);

        return $type;
    }
}
