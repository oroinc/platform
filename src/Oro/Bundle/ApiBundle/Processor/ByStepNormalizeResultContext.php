<?php

namespace Oro\Bundle\ApiBundle\Processor;

/**
 * The base execution context for processors for actions that execute processors
 * only from one group at the same time.
 */
class ByStepNormalizeResultContext extends NormalizeResultContext
{
    /** the name of failed group */
    private const FAILED_GROUP = 'failedGroup';

    /**
     * Gets the name of failed group.
     *
     * @return string|null
     */
    public function getFailedGroup()
    {
        return $this->get(self::FAILED_GROUP);
    }

    /**
     * Sets the name of failed group.
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
