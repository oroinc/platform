<?php

namespace Oro\Bundle\EntityBundle\Handler;

use Psr\Container\ContainerInterface;

/**
 * The registry that allows to get an extension for handlers responsible to implement
 * a business logic to delete a specific entity type.
 */
class EntityDeleteHandlerExtensionRegistry
{
    /** @var ContainerInterface */
    private $extensionContainer;

    public function __construct(ContainerInterface $extensionContainer)
    {
        $this->extensionContainer = $extensionContainer;
    }

    /**
     * Gets an extension for handler responsible to delete the given entity type.
     */
    public function getHandlerExtension(string $entityClass): EntityDeleteHandlerExtensionInterface
    {
        if ($this->extensionContainer->has($entityClass)) {
            return $this->extensionContainer->get($entityClass);
        }

        return $this->extensionContainer->get('default');
    }
}
