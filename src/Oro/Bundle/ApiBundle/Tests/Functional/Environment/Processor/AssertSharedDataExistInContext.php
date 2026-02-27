<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class AssertSharedDataExistInContext implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$this->isDataSharingExpected($context)) {
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

    private function isDataSharingExpected(Context $context): bool
    {
        if ($context->getRequestType()->contains(RequestType::BATCH)) {
            if (ApiAction::GET === $context->getAction() && AsyncOperation::class === $context->getClassName()) {
                /**
                 * data are not shared between "update_list" and "get" action for synchronous batch operations
                 * @see \Oro\Bundle\ApiBundle\Processor\UpdateList\LoadNormalizedAsyncOperation::process
                 */
                return false;
            }
            if (ApiAction::GET_LIST === $context->getAction()) {
                /**
                 * data are not shared between "update_list" and "get_list" action for synchronous batch operations
                 * @see \Oro\Bundle\ApiBundle\Processor\UpdateList\ProcessSynchronousOperation::normalizeEntities
                 */
                return false;
            }
        }

        return true;
    }
}
