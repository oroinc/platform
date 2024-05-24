<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Stub;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Command\Command;

class TestCronCommandWithArbitraryName extends Command implements CronCommandScheduleDefinitionInterface
{
    protected static $defaultName = 'test:cron:command:with:arbitrary:name';

    public function getDefaultDefinition(): string
    {
        return '0 0 * * *';
    }
}
