<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Command;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateVisibilitiesTopic;
use Oro\Bundle\EmailBundle\Command\UpdateVisibilitiesCommand;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\Testing\Command\CommandTestingTrait;

class UpdateVisibilitiesCommandTest extends \PHPUnit\Framework\TestCase
{
    use CommandTestingTrait;
    use MessageQueueExtension;

    /** @var UpdateVisibilitiesCommand */
    private $command;

    protected function setUp(): void
    {
        $this->command = new UpdateVisibilitiesCommand(self::getMessageProducer());
    }

    public function testExecuteSuccess()
    {
        $commandTester = $this->doExecuteCommand($this->command);

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, '[OK] Update of visibilities has been scheduled.');

        self::assertMessageSent(UpdateVisibilitiesTopic::getName(), []);
    }
}
