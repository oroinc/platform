<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class InitializeSharedDataForBatchUpdate implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        if ($context->getSharedData()->has('test')) {
            throw new RuntimeException(sprintf(
                'Shared data already initialized. Action: %s. Class: %s.',
                $context->getAction(),
                $context->getClassName()
            ));
        }

        $context->getSharedData()->set('test', $context->getAction());
    }
}
