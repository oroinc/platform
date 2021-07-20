<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub;

use Oro\Bundle\BatchBundle\Step\StepExecutionWarningHandlerInterface;

class WarningHandler implements StepExecutionWarningHandlerInterface
{
    /**
     * @var array
     */
    private $warnings = [];

    /**
     * @inheritDoc
     */
    public function handleWarning($element, $name, $reason, array $reasonParameters, $item): void
    {
        $this->warnings[] = [$element, $name, $reason, $reasonParameters, $item];
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
