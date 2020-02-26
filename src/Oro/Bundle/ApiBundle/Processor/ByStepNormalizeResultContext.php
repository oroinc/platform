<?php

namespace Oro\Bundle\ApiBundle\Processor;

/**
 * The base execution context for processors for actions that execute processors
 * only from one group at the same time.
 */
class ByStepNormalizeResultContext extends NormalizeResultContext
{
    /** the name of the last group that execution was finished with an error or an exception */
    private const FAILED_GROUP = 'failedGroup';

    /**
     * Gets the name of the last group that execution was finished with an error or an exception.
     *
     * @return string|null
     */
    public function getFailedGroup()
    {
        return $this->get(self::FAILED_GROUP);
    }

    /**
     * Sets the name of the last group that execution was finished with an error or an exception.
     *
     * @param string $groupName
     */
    public function setFailedGroup($groupName)
    {
        if ($groupName) {
            $this->set(self::FAILED_GROUP, $groupName);
        } else {
            $this->remove(self::FAILED_GROUP);
        }
    }
}
