<?php

namespace Oro\Component\ChainProcessor;

/**
 * This applicable checker is used to skip processors are not included in a requested range of groups.
 * Use setFirstGroup and setLastGroup of the execution context to define the range.
 */
class GroupRangeApplicableChecker implements ApplicableCheckerInterface, ProcessorBagAwareApplicableCheckerInterface
{
    /** @var ProcessorBagInterface|null */
    protected $processorBag;

    /** @var string|null */
    protected $action;

    /** @var array|null */
    protected $groups;

    /**
     * {@inheritdoc}
     */
    public function setProcessorBag(ProcessorBagInterface $processorBag = null)
    {
        $this->processorBag = $processorBag;
        $this->action = null;
        $this->groups = null;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
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

    /**
     * Makes sure groups are loaded
     *
     * @param string $action
     */
    protected function ensureGroupsLoaded($action)
    {
        if ($action !== $this->action) {
            $this->action = $action;
            $this->groups = \array_flip($this->processorBag->getActionGroups($action));
        }
    }
}
