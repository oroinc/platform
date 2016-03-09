<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates loaded object.
 */
class ValidateObject implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context->hasObject()) {
            throw new NotFoundHttpException('Unsupported request.');
        } elseif (null === $context->getObject()) {
            throw new NotFoundHttpException('An entity with the requested identifier does not exist.');
        }
    }
}
