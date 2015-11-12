<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class ValidateResult implements ProcessorInterface
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
                throw new \RuntimeException('Unsupported request.');
            } else {
                throw new \RuntimeException(
                    sprintf(
                        'Unsupported query type: %s.',
                        is_object($query) ? get_class($query) : gettype($query)
                    )
                );
            }
        }
    }
}
