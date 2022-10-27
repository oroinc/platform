<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

/**
 * Registry of type casting handlers (DI tag: oro_search.type_cast_handler).
 */
class TypeCastingHandlerRegistry
{
    /**
     * @var TypeCastingHandlerInterface[]
     */
    private $handlers;

    public function __construct(\Traversable $handlers)
    {
        $this->handlers = iterator_to_array($handlers);
    }

    public function get(string $type): TypeCastingHandlerInterface
    {
        if (!array_key_exists($type, $this->handlers)) {
            throw new \LogicException(sprintf('No registered typecasting handlers that support the "%s" type.', $type));
        }

        return $this->handlers[$type];
    }
}
