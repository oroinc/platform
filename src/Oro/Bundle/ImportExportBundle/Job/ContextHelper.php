<?php

namespace Oro\Bundle\ImportExportBundle\Job;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Helper utility for merging import/export context counters.
 *
 * This class provides static methods to aggregate operation counters from multiple
 * contexts into a single context. It is used when combining results from multiple
 * import/export jobs or batches to produce consolidated statistics.
 */
class ContextHelper
{
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
