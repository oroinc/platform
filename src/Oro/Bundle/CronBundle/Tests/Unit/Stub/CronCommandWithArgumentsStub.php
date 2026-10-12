<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Stub;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleArgumentsInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Command\Command;

class CronCommandWithArgumentsStub extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandScheduleArgumentsInterface
{
    private array $defaultArguments = [];

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '*/1 * * * *';
    }

    #[\Override]
    public function getDefaultArguments(): array
    {
        return $this->defaultArguments;
    }

    public function setDefaultArguments(array $defaultArguments): void
    {
        $this->defaultArguments = $defaultArguments;
    }
}
