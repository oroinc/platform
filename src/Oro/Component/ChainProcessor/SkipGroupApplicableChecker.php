<?php

namespace Oro\Component\ChainProcessor;

/**
 * This applicable checker is used to skip processors are included in groups are requested to be skipped.
 * Use skipGroup and undoGroupSkipping of the execution context to manage groups to be skipped.
 */
class SkipGroupApplicableChecker implements ApplicableCheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
    {
        if (empty($processorAttributes['group']) || !$context->hasSkippedGroups()) {
            return self::ABSTAIN;
        }

        return \in_array($processorAttributes['group'], $context->getSkippedGroups(), true)
            ? self::NOT_APPLICABLE
            : self::ABSTAIN;
    }
}
