<?php

namespace Oro\Component\ChainProcessor;

/**
 * This applicable checker is used to skip processors are not included in a requested range of groups.
 * Use setFirstGroup and setLastGroup of the execution context to define the range.
 */
class GroupRangeApplicableChecker implements ApplicableCheckerInterface, ProcessorBagAwareApplicableCheckerInterface
{
    private ?ProcessorBagInterface $processorBag = null;
    private ?string $action = null;
    private ?array $groups = null;

    /**
     * {@inheritDoc}
     */
    public function setProcessorBag(ProcessorBagInterface $processorBag): void
    {
        $this->processorBag = $processorBag;
        $this->action = null;
        $this->groups = null;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes): int
    {
        if (null === $this->processorBag
            || empty($processorAttributes['group'])
            || (!$context->getFirstGroup() && !$context->getLastGroup())
        ) {
            return self::ABSTAIN;
        }

        $this->ensureGroupsLoaded($context->getAction());

        $group = $processorAttributes['group'];
        if (isset($this->groups[$group])) {
            $groupIndex = $this->groups[$group];

            $firstGroup = $context->getFirstGroup();
            if ($firstGroup && isset($this->groups[$firstGroup]) && $groupIndex < $this->groups[$firstGroup]) {
                return self::NOT_APPLICABLE;
            }
            $lastGroup = $context->getLastGroup();
            if ($lastGroup && isset($this->groups[$lastGroup]) && $groupIndex > $this->groups[$lastGroup]) {
                return self::NOT_APPLICABLE;
            }
        }

        return self::ABSTAIN;
    }

    private function ensureGroupsLoaded(string $action): void
    {
        if ($action !== $this->action) {
            $this->action = $action;
            $this->groups = array_flip($this->processorBag->getActionGroups($action));
        }
    }
}
