<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;

/**
 * This iterator implements a group related checks in more performant way than
 * if it was implemented in applicable checkers.
 * It is very important for Data API, especially for generation of API documentation
 * because huge number of processors are iterated in this case.
 */
class OptimizedProcessorIterator extends ProcessorIterator
{
    /** @var array */
    protected $groups;

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
        if ($this->context->hasSkippedGroups()) {
            // skip all processors which belong to skipped groups
            $this->index++;
            while (in_array($this->getGroup(), $this->context->getSkippedGroups(), true)) {
                $this->index++;
            }
            $this->index--;
        }

        $firstGroup = $this->context->getFirstGroup();
        if ($firstGroup) {
            $this->processFirstGroup($firstGroup);
        }

        $lastGroup = $this->context->getLastGroup();
        if ($lastGroup) {
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
     * @param string $firstGroup
     */
    protected function processFirstGroup($firstGroup)
    {
        $groups = $this->getGroups();
        if (isset($groups[$firstGroup])) {
            $firstGroupIndex = $groups[$firstGroup];
            $this->index++;
            while ($this->index <= $this->maxIndex) {
                $group = $this->getGroup();
                if (!$group || $groups[$group] >= $firstGroupIndex) {
                    break;
                }
                $this->index++;
            }
            $this->index--;
        }
    }

    /**
     * @param string $lastGroup
     */
    protected function processLastGroup($lastGroup)
    {
        if ($this->getGroup() === $lastGroup) {
            // the current processor belongs to the last group from which processors should be executed
            $this->index++;
            if ($this->getGroup() !== $lastGroup) {
                // skip all following processors as all processors from the last group have been iterated
                $this->nextUngrouped();
            }
            $this->index--;
        } else {
            $this->index++;
            $group = $this->getGroup();
            if ($group) {
                $groups = $this->getGroups();
                if (isset($groups[$lastGroup]) && $groups[$group] > $groups[$lastGroup]) {
                    $this->nextUngrouped();
                }
            }
            $this->index--;
        }
    }

    /**
     * Moves to ungrouped processor
     */
    protected function nextUngrouped()
    {
        while ($this->index <= $this->maxIndex) {
            if (!$this->getGroup()) {
                break;
            }
            $this->index++;
        }
    }

    /**
     * Returns groups for the given action
     *
     * @return array [group name => group index, ...]
     */
    protected function getGroups()
    {
        if (null === $this->groups) {
            $this->groups = $this->loadGroups();
        }

        return $this->groups;
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
