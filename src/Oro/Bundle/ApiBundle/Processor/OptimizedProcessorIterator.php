<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;

/**
 * This iterator implements a group related checks in more performant way than
 * if it was implemented in applicable checkers.
 * It is very important for Data API, especially for generation of API documentation
 * because huge number of processors are iterated in this case.
 */
class OptimizedProcessorIterator extends ProcessorIterator
{
    /** @var array [group name => group index, ...] */
    protected $groups;

    /**
     * @param array                      $processors
     * @param ComponentContextInterface  $context
     * @param ApplicableCheckerInterface $applicableChecker
     * @param ProcessorFactoryInterface  $processorFactory
     */
    public function __construct(
        array $processors,
        ComponentContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorFactoryInterface $processorFactory
    ) {
        parent::__construct($processors, $context, $applicableChecker, $processorFactory);
        $this->groups = $this->loadGroups();
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
        if (!empty($skippedGroups)) {
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

        $applicable = $this->applicableChecker->isApplicable(
            $this->context,
            $this->processors[$this->index]['attributes']
        );

        return $applicable !== ApplicableCheckerInterface::NOT_APPLICABLE;
    }

    /**
     * Skips all processors which belong to skipped groups
     *
     * @param string[] $skippedGroups
     */
    protected function processSkippedGroups($skippedGroups)
    {
        $index = $this->index + 1;
        while ($index <= $this->maxIndex && in_array($this->getGroupByIndex($index), $skippedGroups, true)) {
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
            $group = $this->getGroupByIndex($index);
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

        if (-1 !== $this->index && $this->getGroupByIndex($this->index) === $lastGroup) {
            // the current processor belongs to the last group from which processors should be executed
            if ($this->getGroupByIndex($index) !== $lastGroup) {
                // skip all following processors as all processors from the last group have been iterated
                $index = $this->getIndexOfUngroupedProcessor($index);
            }
        } else {
            $group = $this->getGroupByIndex($index);
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
        while ($index <= $this->maxIndex) {
            if (!$this->getGroupByIndex($index)) {
                break;
            }
            $index++;
        }

        return $index;
    }

    /**
     * @param int $index
     *
     * @return string null
     */
    protected function getGroupByIndex($index)
    {
        return isset($this->processors[$index]['attributes']['group'])
            ? $this->processors[$index]['attributes']['group']
            : null;
    }

    /**
     * Loads groups for the given action
     *
     * @return array [group name => group index, ...]
     */
    protected function loadGroups()
    {
        $result = [];
        $groupIndex = 0;
        foreach ($this->processors as $processor) {
            if (!isset($processor['attributes']['group'])) {
                continue;
            }
            $group = $processor['attributes']['group'];
            if (!isset($result[$group])) {
                $result[$group] = $groupIndex;
                $groupIndex++;
            }
        }

        return $result;
    }
}
