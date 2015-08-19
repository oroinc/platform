<?php

namespace Oro\Bundle\ImportExportBundle\Job;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

class ContextHelper
{
    /**
     * @param ContextInterface $firstContext
     * @param ContextInterface $context
     */
    public static function mergeContextCounters(ContextInterface $firstContext, ContextInterface $context)
    {
        $firstContext->incrementReadCount($context->getReadCount());
        $firstContext->incrementAddCount($context->getAddCount());
        $firstContext->incrementUpdateCount($context->getUpdateCount());
        $firstContext->incrementReplaceCount($context->getReplaceCount());
        $firstContext->incrementDeleteCount($context->getDeleteCount());
        $firstContext->incrementErrorEntriesCount($context->getErrorEntriesCount());
    }
}
