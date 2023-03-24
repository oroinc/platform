<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitGarbageCollectionExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitObjectExtension;
use Oro\Component\MessageQueue\Tests\Unit\Consumption\Mock\LimitsExtensionsCommand;
use Symfony\Component\Console\Tester\CommandTester;

class LimitsExtensionsCommandTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldAddExtensionsOptions()
    {
        $trait = new LimitsExtensionsCommand('name');

        $options = $trait->getDefinition()->getOptions();

        $this->assertCount(6, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
        $this->assertArrayHasKey('object-limit', $options);
        $this->assertArrayHasKey('gc-limit', $options);
        self::assertArrayHasKey('stop-when-unique-jobs-processed', $options);
    }

    public function testShouldAddMessageLimitExtension()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--message-limit' => 5,
        ]);

        $result = $command->getExtensions();

        $this->assertCount(1, $result);

        $this->assertInstanceOf(LimitConsumedMessagesExtension::class, $result[0]);
    }

    public function testShouldAddTimeLimitExtension()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--time-limit' => '+5',
        ]);

        $result = $command->getExtensions();

        $this->assertCount(1, $result);

        $this->assertInstanceOf(LimitConsumptionTimeExtension::class, $result[0]);
    }

    public function testShouldThrowExceptionIfTimeLimitExpressionIsNotValid()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to parse time string (time is not valid) at position');

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

        $this->assertCount(1, $result);

        $this->assertInstanceOf(LimitConsumerMemoryExtension::class, $result[0]);
    }

    public function testShouldAddObjectLimitExtension()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--object-limit' => 5,
        ]);

        $result = $command->getExtensions();

        $this->assertCount(1, $result);

        $this->assertInstanceOf(LimitObjectExtension::class, $result[0]);
    }

    public function testShouldAddGCLimitExtension()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--gc-limit' => 5,
        ]);

        $result = $command->getExtensions();

        $this->assertCount(1, $result);

        $this->assertInstanceOf(LimitGarbageCollectionExtension::class, $result[0]);
    }

    public function testShouldAddFiveLimitExtensions()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--time-limit' => '+5',
            '--memory-limit' => 5,
            '--message-limit' => 5,
            '--object-limit' => 5,
            '--gc-limit' => 5,
        ]);

        $result = $command->getExtensions();

        $this->assertCount(5, $result);

        $this->assertInstanceOf(LimitConsumedMessagesExtension::class, $result[0]);
        $this->assertInstanceOf(LimitConsumptionTimeExtension::class, $result[1]);
        $this->assertInstanceOf(LimitConsumerMemoryExtension::class, $result[2]);
        $this->assertInstanceOf(LimitObjectExtension::class, $result[3]);
        $this->assertInstanceOf(LimitGarbageCollectionExtension::class, $result[4]);
    }
}
