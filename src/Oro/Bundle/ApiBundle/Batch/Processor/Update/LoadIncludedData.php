<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorRegistry;
use Oro\Bundle\ApiBundle\Batch\IncludeMapManager;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads included data and locks the include index if it is required.
 */
class LoadIncludedData implements ProcessorInterface
{
    public const OPERATION_NAME = 'load_included_data';

    private IncludeAccessorRegistry $includeAccessorRegistry;
    private IncludeMapManager $includeMapManager;

    public function __construct(
        IncludeAccessorRegistry $includeAccessorRegistry,
        IncludeMapManager $includeMapManager
    ) {
        $this->includeAccessorRegistry = $includeAccessorRegistry;
        $this->includeMapManager = $includeMapManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // included data were already loaded
            return;
        }

        $data = $context->getResult();
        if (null === $data) {
            // data were not loaded
            return;
        }

        $includeAccessor = $this->includeAccessorRegistry->getAccessor($context->getRequestType());
        if (null !== $includeAccessor) {
            $includedData = $this->includeMapManager->getIncludedItems(
                $context->getFileManager(),
                $context->getOperationId(),
                $includeAccessor,
                $data
            );
            if (null === $includedData) {
                $context->setRetryReason(
                    'Not possible to get included items now because the lock for the include index cannot be acquired.'
                );
                $context->skipGroup(ApiActionGroup::INITIALIZE);
            } else {
                $context->setIncludedData($includedData);
            }
        }
        $context->setProcessed(self::OPERATION_NAME);
    }
}
