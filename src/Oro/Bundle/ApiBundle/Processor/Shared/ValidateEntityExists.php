<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Makes sure that an entity is loaded.
 */
class ValidateEntityExists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context->hasResult() || null === $context->getResult()) {
            throw new NotFoundHttpException('An entity with the requested identifier does not exist.');
        }
    }
}
