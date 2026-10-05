<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Stub;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleArgumentsInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Command\Command;

class TestCronCommandWithArguments extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandScheduleArgumentsInterface
{
    protected static $defaultName = 'oro:cron:test:with_arguments';

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
