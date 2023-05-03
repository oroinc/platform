<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\CustomizeDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class AssertSharedDataExistInCustomizeDataContext implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeDataContext $context */

        if (!$context->getSharedData()->has('test')) {
            throw new RuntimeException(sprintf(
                'Shared data is not initialized. Action: %s. Class: %s.',
                $context->getAction(),
                $context->getClassName()
            ));
        }
    }
}
