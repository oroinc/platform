<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;

/**
 * Delegates the handling of ApiDoc annotation to all child handlers.
 */
class ChainApiDocAnnotationHandler implements ApiDocAnnotationHandlerInterface
{
    /** @var iterable<ApiDocAnnotationHandlerInterface> */
    private iterable $handlers;

    /**
     * @param iterable<ApiDocAnnotationHandlerInterface> $handlers
     */
    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ApiDoc $annotation, Route $route): void
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($annotation, $route);
        }
    }
}
