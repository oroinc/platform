<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Psr\Container\ContainerInterface;

/**
 * The container for form handlers.
 */
class FormHandlerRegistry
{
    public const DEFAULT_HANDLER_NAME = 'default';

    /** @var ContainerInterface */
    private $handlers;

    /**
     * @param ContainerInterface $handlers
     */
    public function __construct(ContainerInterface $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function has(string $alias): bool
    {
        return $this->handlers->has($alias);
    }

    /**
     * @param string $alias
     *
     * @return FormHandlerInterface
     */
    public function get(string $alias): FormHandlerInterface
    {
        if (!$this->handlers->has($alias)) {
            throw new \LogicException(sprintf('Unknown form handler with alias "%s".', $alias));
        }

        return $this->handlers->get($alias);
    }
}
