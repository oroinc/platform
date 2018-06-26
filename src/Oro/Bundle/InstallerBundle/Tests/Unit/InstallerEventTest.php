<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit;

use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\InstallerEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class InstallerEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCommandExecutor()
    {
        /** @var CommandExecutor $commandExecutor */
        $commandExecutor = $this->createMock(CommandExecutor::class);

        $event = new InstallerEvent(new Command('test'), new StringInput(''), new NullOutput(), $commandExecutor);

        $this->assertSame($commandExecutor, $event->getCommandExecutor());
    }
}
