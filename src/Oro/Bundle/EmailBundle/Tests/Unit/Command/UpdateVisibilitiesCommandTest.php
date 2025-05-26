<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Command;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateVisibilitiesTopic;
use Oro\Bundle\EmailBundle\Command\UpdateVisibilitiesCommand;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\Testing\Command\CommandTestingTrait;
use PHPUnit\Framework\TestCase;

class UpdateVisibilitiesCommandTest extends TestCase
{
    use CommandTestingTrait;
    use MessageQueueExtension;

    private UpdateVisibilitiesCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->command = new UpdateVisibilitiesCommand(self::getMessageProducer());
    }

    public function testExecuteSuccess(): void
    {
        $commandTester = $this->doExecuteCommand($this->command);

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, '[OK] Update of visibilities has been scheduled.');

        self::assertMessageSent(UpdateVisibilitiesTopic::getName(), []);
    }
}
