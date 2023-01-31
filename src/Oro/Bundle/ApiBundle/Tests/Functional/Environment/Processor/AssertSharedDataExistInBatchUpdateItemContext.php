<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class AssertSharedDataExistInBatchUpdateItemContext implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateItemContext $context */

        if (!$context->getSharedData()->has('test')) {
            throw new RuntimeException(sprintf(
                'Shared data is not initialized. Action: %s. Class: %s. Target Action: %s.',
                $context->getAction(),
                $context->getClassName(),
                $context->getTargetAction()
            ));
        }
    }
}
