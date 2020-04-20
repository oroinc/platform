<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

class BatchUpdateExceptionController
{
    /** @var string[] */
    private $failedGroups = [];

    /**
     * @return string[]
     */
    public function getFailedGroups(): array
    {
        return $this->failedGroups;
    }

    /**
     * @param string[] $failedGroups
     */
    public function setFailedGroups(array $failedGroups): void
    {
        $this->failedGroups = $failedGroups;
    }

    public function clear()
    {
        $this->failedGroups = [];
    }
}
