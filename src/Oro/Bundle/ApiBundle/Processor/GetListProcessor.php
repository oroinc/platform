<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ChainProcessor;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;

class GetListProcessor extends ChainProcessor
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        $this->executeProcessors($context);

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
