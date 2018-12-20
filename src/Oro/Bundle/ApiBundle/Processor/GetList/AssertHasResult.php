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
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */
        if (!$context->hasResult()) {
            $query = $context->getQuery();
            if (null === $query) {
                throw new NotFoundHttpException('Unsupported request.');
            } else {
                throw new NotFoundHttpException(
                    sprintf(
                        'Unsupported query type: %s.',
                        is_object($query) ? get_class($query) : gettype($query)
                    )
                );
            }
        } elseif (!is_array($context->getResult())) {
            throw new NotFoundHttpException('Getting a list of entities failed.');
        }
    }
}
