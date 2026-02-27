<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\CustomizeDataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class AssertSharedDataExistInCustomizeDataContext implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeDataContext $context */

        if (!$this->isDataSharingExpected($context->getAction(), $context->getRequestType())) {
            if (!$context->getSharedData()->has('test')) {
                return;
            }
            throw new RuntimeException(\sprintf(
                'Shared data should not be initialized. Action: %s. Class: %s.',
                $context->getAction(),
                $context->getClassName()
            ));
        }

        if (!$context->getSharedData()->has('test')) {
            throw new RuntimeException(\sprintf(
                'Shared data is not initialized. Action: %s. Class: %s.',
                $context->getAction(),
                $context->getClassName()
            ));
        }
    }

    private function isDataSharingExpected(string $action, RequestType $requestType): bool
    {
        if ('customize_loaded_data' === $action && $requestType->contains(RequestType::BATCH)) {
            /**
             * data are not shared between "update_list" and "get_list" action for synchronous batch operations
             * @see \Oro\Bundle\ApiBundle\Processor\UpdateList\ProcessSynchronousOperation::normalizeEntities
             */
            return false;
        }

        return true;
    }
}
