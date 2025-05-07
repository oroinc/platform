<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Psr\Container\ContainerInterface;

/**
 * Registry of type casting handlers (DI tag: oro_search.type_cast_handler).
 */
class TypeCastingHandlerRegistry
{
    public function __construct(
        private readonly ContainerInterface $handlers
    ) {
    }

    public function get(string $type): TypeCastingHandlerInterface
    {
        if (!$this->handlers->has($type)) {
            throw new \LogicException(\sprintf(
                'No registered typecasting handlers that support the "%s" type.',
                $type
            ));
        }

        return $this->handlers->get($type);
    }
}
