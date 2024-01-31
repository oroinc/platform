<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Stub;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Command\Command;

class LazyTestCronCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    protected static $defaultName = 'oro:cron:test:lazy';
    protected static $defaultDescription = 'Test lazy CRON command.';

    public function getDefaultDefinition(): string
    {
        return '0 0 * * *';
    }
}
