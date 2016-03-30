<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether an entity is loaded.
 */
class ValidateLoadedResult implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context->hasResult()) {
            throw new NotFoundHttpException('Unsupported request.');
        } elseif (null === $context->getResult()) {
            throw new NotFoundHttpException('An entity with the requested identifier does not exist.');
        }
    }
}
