<?php

namespace Oro\Component\Layout;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\Layout\BlockTypeFactoryInterface;
use Oro\Component\Layout\Exception\InvalidArgumentException;

class DependencyInjectionBlockTypeFactory implements BlockTypeFactoryInterface
{
    protected $container;
    protected $typeServiceIds;

    public function __construct(ContainerInterface $container, array $typeServiceIds)
    {
        $this->container = $container;
        $this->typeServiceIds = $typeServiceIds;
    }

    /**
     * @param string $name
     *
     * @return null|object
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function getType($name)
    {
        if (!isset($this->typeServiceIds[$name])) {
            return null;
        }

        $type = $this->container->get($this->typeServiceIds[$name]);

        if ($type->getName() !== $name) {
            throw new InvalidArgumentException(
                sprintf(
                    'The type name specified for the service "%s" does not match the actual name. Expected "%s", ' .
                    'given "%s"',
                    $this->typeServiceIds[$name],
                    $name,
                    $type->getName()
                )
            );
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockType($name)
    {
        return $this->getType($name);
    }
}
