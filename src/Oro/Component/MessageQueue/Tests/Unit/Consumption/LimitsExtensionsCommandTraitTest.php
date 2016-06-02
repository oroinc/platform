<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Tests\Unit\Consumption\Mock\LimitsExtensionsCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Tests\Output\TestOutput;

class LimitsExtensionsCommandTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldAddExtensionsOptions()
    {
        $trait = new LimitsExtensionsCommand('name');

        $options = $trait->getDefinition()->getOptions();

        $this->assertCount(3, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
    }

    public function testByDefaultShouldReturnOnlyLoggerExtension()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $result = $command->getExtensions();

        $this->assertCount(1, $result);

        $this->assertInstanceOf(LoggerExtension::class, $result[0]);
    }

    public function testShouldAddMessageLimitExtension()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--message-limit' => 5,
        ]);

        $result = $command->getExtensions();

        $this->assertCount(2, $result);

        $this->assertInstanceOf(LoggerExtension::class, $result[0]);
        $this->assertInstanceOf(LimitConsumedMessagesExtension::class, $result[1]);
    }

    public function testShouldAddTimeLimitExtension()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--time-limit' => '+5',
        ]);

        $result = $command->getExtensions();

        $this->assertCount(2, $result);

        $this->assertInstanceOf(LoggerExtension::class, $result[0]);
        $this->assertInstanceOf(LimitConsumptionTimeExtension::class, $result[1]);
    }

    public function testShouldThrowExceptionIfTimeLimitExpressionIsNotValid()
    {
        $this->setExpectedException(\Exception::class, 'Failed to parse time string (time is not valid) at position');

        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--time-limit' => 'time is not valid',
        ]);

        $command->getExtensions();
    }

    public function testShouldAddMemoryLimitExtension()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--memory-limit' => 5,
        ]);

        $result = $command->getExtensions();

        $this->assertCount(2, $result);

        $this->assertInstanceOf(LoggerExtension::class, $result[0]);
        $this->assertInstanceOf(LimitConsumerMemoryExtension::class, $result[1]);
    }
}
