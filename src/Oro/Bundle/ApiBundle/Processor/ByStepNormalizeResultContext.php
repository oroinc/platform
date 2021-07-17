<?php

namespace Oro\Bundle\ApiBundle\Processor;

/**
 * The base execution context for processors for actions that execute processors
 * only from one group at the same time.
 */
class ByStepNormalizeResultContext extends NormalizeResultContext
{
    /**
     * the name of the group after that processors from "normalize_result" group are executed
     * if no errors are occurred
     */
    private const SOURCE_GROUP = 'sourceGroup';

    /** the name of the last group that execution was finished with an error or an exception */
    private const FAILED_GROUP = 'failedGroup';

    /**
     * Gets the name of the group after that processors from "normalize_result" group are executed
     * if no errors are occurred.
     */
    public function getSourceGroup(): ?string
    {
        return $this->get(self::SOURCE_GROUP);
    }

    /**
     * Sets the name of the group after that processors from "normalize_result" group are executed
     * if no errors are occurred.
     */
    public function setSourceGroup(?string $groupName): void
    {
        if (null === $groupName) {
            $this->remove(self::SOURCE_GROUP);
        } else {
            $this->set(self::SOURCE_GROUP, $groupName);
        }
    }

    /**
     * Gets the name of the last group that execution was finished with an error or an exception.
     */
    public function getFailedGroup(): ?string
    {
        return $this->get(self::FAILED_GROUP);
    }

    /**
     * Sets the name of the last group that execution was finished with an error or an exception.
     */
    public function setFailedGroup(?string $groupName): void
    {
        if (null === $groupName) {
            $this->remove(self::FAILED_GROUP);
        } else {
            $this->set(self::FAILED_GROUP, $groupName);
        }
    }
}
