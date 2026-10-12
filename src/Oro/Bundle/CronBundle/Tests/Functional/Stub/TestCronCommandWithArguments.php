<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Stub;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleArgumentsInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'oro:cron:test:with_arguments')]
class TestCronCommandWithArguments extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandScheduleArgumentsInterface
{
    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '0 0 * * *';
    }

    #[\Override]
    public function getDefaultArguments(): array
    {
        return ['--process-timeout=1200'];
    }
}
