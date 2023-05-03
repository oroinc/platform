<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Makes sure that the valid result was added to the context.
 */
class AssertHasResult implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var GetListContext $context */

        if (!$context->hasResult()) {
            $query = $context->getQuery();
            if (null !== $query) {
                throw new NotFoundHttpException(sprintf('Unsupported query type: %s.', get_debug_type($query)));
            }
            throw new NotFoundHttpException('Unsupported request.');
        }
        if (!\is_array($context->getResult())) {
            throw new NotFoundHttpException('Getting a list of entities failed.');
        }
    }
}
