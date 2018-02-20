<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;

/**
 * Delegates the handling of ApiDoc annotation to all child handlers.
 */
class ChainApiDocAnnotationHandler implements ApiDocAnnotationHandlerInterface
{
    /** @var ApiDocAnnotationHandlerInterface[] */
    private $handlers = [];

    /**
     * Adds a handler to the chain.
     *
     * @param ApiDocAnnotationHandlerInterface $handler
     */
    public function addHandler(ApiDocAnnotationHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ApiDoc $annotation, Route $route)
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($annotation, $route);
        }
    }
}
