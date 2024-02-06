<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;

/**
 * This iterator implements a group related checks in more performant way than
 * if it was implemented in applicable checkers.
 * It is very important for API, especially for generation of API documentation
 * because huge number of processors are iterated in this case.
 */
class OptimizedProcessorIterator extends ProcessorIterator
{
    /** @var array [group name => group index, ...] */
    private $groups;

    /** @var array [processor index => group name, ...] */
    private $processorGroups;

    /**
     * @param array                      $processors      [[processor id, [attr name => attr value, ...]], ...]
     * @param array                      $groups          [group name => group index, ...]
     * @param array                      $processorGroups [processor index => group name, ...]
     * @param ComponentContextInterface  $context
     * @param ApplicableCheckerInterface $applicableChecker
     * @param ProcessorRegistryInterface $processorRegistry
     */
    public function __construct(
        array $processors,
        array $groups,
        array $processorGroups,
        ComponentContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorRegistryInterface $processorRegistry
    ) {
        parent::__construct($processors, $context, $applicableChecker, $processorRegistry);
        $this->groups = $groups;
        $this->processorGroups = $processorGroups;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        if (-1 === $this->index || !$this->valid()) {
            return null;
        }

        return $this->processorGroups[$this->index];
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessorAttributes()
    {
        $attributes = parent::getProcessorAttributes();
        if (null !== $attributes) {
            $group = $this->processorGroups[$this->index];
            if ($group) {
                $attributes['group'] = $group;
            }
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    protected function nextApplicable()
    {
        while ($this->index <= $this->maxIndex) {
            if ($this->tryMoveToNextApplicable()) {
                break;
            }
        }
    }

    /**
     * Tries to move to the next processor that can be executed
     *
     * @return bool TRUE if a processor was found; otherwise, FALSE
     */
    protected function tryMoveToNextApplicable()
    {
        $skippedGroups = $this->context->getSkippedGroups();
        if ($skippedGroups) {
            $this->processSkippedGroups($skippedGroups);
        }

        $firstGroup = $this->context->getFirstGroup();
        if ($firstGroup && $this->index <= $this->maxIndex) {
            $this->processFirstGroup($firstGroup);
        }

        $lastGroup = $this->context->getLastGroup();
        if ($lastGroup && $this->index <= $this->maxIndex) {
            $this->processLastGroup($lastGroup);
        }

        $this->index++;
        if ($this->index > $this->maxIndex) {
            return false;
        }

        return ApplicableCheckerInterface::NOT_APPLICABLE !== $this->isApplicable();
    }

    /**
     * Skips all processors which belong to skipped groups
     *
     * @param string[] $skippedGroups
     */
    protected function processSkippedGroups($skippedGroups)
    {
        $index = $this->index + 1;
        while ($index <= $this->maxIndex && \in_array($this->processorGroups[$index], $skippedGroups, true)) {
            $index++;
        }
        $this->index = $index - 1;
    }

    /**
     * @param string $firstGroup
     */
    protected function processFirstGroup($firstGroup)
    {
        if (!isset($this->groups[$firstGroup])) {
            return;
        }

        $firstGroupIndex = $this->groups[$firstGroup];
        $index = $this->index + 1;
        while ($index <= $this->maxIndex) {
            $group = $this->processorGroups[$index];
            if (!$group || $this->groups[$group] >= $firstGroupIndex) {
                break;
            }
            $index++;
        }
        $this->index = $index - 1;
    }

    /**
     * @param string $lastGroup
     */
    protected function processLastGroup($lastGroup)
    {
        $index = $this->index + 1;
        if ($index > $this->maxIndex) {
            return;
        }

        if (-1 !== $this->index && $this->processorGroups[$index] === $lastGroup) {
            // the current processor belongs to the last group from which processors should be executed
            if ($this->processorGroups[$index] !== $lastGroup) {
                // skip all following processors as all processors from the last group have been iterated
                $index = $this->getIndexOfUngroupedProcessor($index);
            }
        } else {
            $group = $this->processorGroups[$index];
            if ($group && isset($this->groups[$lastGroup]) && $this->groups[$group] > $this->groups[$lastGroup]) {
                $index = $this->getIndexOfUngroupedProcessor($index);
            }
        }
        $this->index = $index - 1;
    }

    /**
     * Returns the index of ungrouped processor
     *
     * @param int $index
     *
     * @return int
     */
    protected function getIndexOfUngroupedProcessor($index)
    {
        $i = $this->maxIndex;
        while ($i > $index) {
            if ($this->processorGroups[$i]) {
                break;
            }
            $i--;
        }

        return $i + 1;
    }
}
