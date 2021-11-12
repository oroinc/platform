<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Context;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Entity\Warning;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StepExecutionProxyContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var StepExecution|\PHPUnit\Framework\MockObject\MockObject */
    private $stepExecution;

    /** @var StepExecutionProxyContext */
    private $context;

    protected function setUp(): void
    {
        $this->stepExecution = $this->createMock(StepExecution::class);

        $this->context = new StepExecutionProxyContext($this->stepExecution);
    }

    public function testAddError()
    {
        $message = 'Error message';

        $this->stepExecution->expects($this->once())
            ->method('addError')
            ->with($message);

        $this->context->addError($message);
    }

    public function testGetErrors()
    {
        $expected = ['Error message'];

        $this->stepExecution->expects($this->once())
            ->method('getErrors')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->context->getErrors());
    }

    public function testIncrementReadCount()
    {
        $this->stepExecution->expects($this->once())
            ->method('setReadCount')
            ->with(2);
        $this->stepExecution->expects(self::exactly(3))
            ->method('getReadCount')
            ->willReturn(1);

        $jobInstance = $this->createMock(JobInstance::class);
        $jobInstance->expects(self::exactly(2))
            ->method('getRawConfiguration')
            ->willReturnOnConsecutiveCalls(['incremented_read' => false], ['incremented_read' => true]);

        $jobExecution = $this->createMock(JobExecution::class);
        $jobExecution->expects(self::exactly(2))
            ->method('getJobInstance')
            ->willReturn($jobInstance);

        $this->stepExecution->expects(self::exactly(2))
            ->method('getJobExecution')
            ->willReturn($jobExecution);

        $this->context->incrementReadCount();
        self::assertEquals(1, $this->context->getReadCount());

        $this->context->incrementReadCount();
        self::assertEquals(1, $this->context->getReadCount());
    }

    public function testGetReadCount()
    {
        $expected = 10;

        $this->stepExecution->expects($this->once())
            ->method('getReadCount')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->context->getReadCount());
    }

    /**
     * @dataProvider incrementCountDataProvider
     */
    public function testIncrement(string $propertyName)
    {
        $expectedCount = 1;

        $executionContext = $this->createMock(ExecutionContext::class);

        $this->stepExecution->expects($this->exactly(2))
            ->method('getExecutionContext')
            ->willReturn($executionContext);

        $executionContext->expects($this->once())
            ->method('get')
            ->with($propertyName)
            ->willReturn($expectedCount);

        $executionContext->expects($this->once())
            ->method('put')
            ->with($propertyName, $expectedCount + 1);

        $method = 'increment' . str_replace('_', '', $propertyName);
        $this->context->$method();
    }

    public function incrementCountDataProvider(): array
    {
        return [
            ['read_offset'],
            ['update_count'],
            ['replace_count'],
            ['delete_count'],
            ['error_entries_count'],
            ['add_count']
        ];
    }

    /**
     * @dataProvider getCountDataProvider
     */
    public function testGetCount(string $propertyName)
    {
        $expectedCount = 1;

        $executionContext = $this->createMock(ExecutionContext::class);

        $this->stepExecution->expects($this->once())
            ->method('getExecutionContext')
            ->willReturn($executionContext);

        $executionContext->expects($this->once())
            ->method('get')
            ->with($propertyName)
            ->willReturn($expectedCount);

        $method = 'get' . str_replace('_', '', $propertyName);
        $this->assertEquals($expectedCount, $this->context->$method());
    }

    public function getCountDataProvider(): array
    {
        return [
            ['read_offset'],
            ['update_count'],
            ['replace_count'],
            ['delete_count'],
            ['error_entries_count'],
            ['add_count']
        ];
    }

    public function testGetConfiguration()
    {
        $expectedConfiguration = ['foo' => 'value'];
        $this->expectGetRawConfiguration($expectedConfiguration);
        $this->assertSame($expectedConfiguration, $this->context->getConfiguration());
    }

    public function testHasConfigurationOption()
    {
        $expectedConfiguration = ['foo' => 'value'];
        $this->expectGetRawConfiguration($expectedConfiguration);

        $this->assertTrue($this->context->hasOption('foo'));
    }

    public function testHasConfigurationOptionUnknown()
    {
        $expectedConfiguration = ['foo' => 'value'];
        $this->expectGetRawConfiguration($expectedConfiguration);

        $this->assertFalse($this->context->hasOption('unknown'));
    }

    public function testGetConfigurationOption()
    {
        $expectedConfiguration = ['foo' => 'value'];
        $this->expectGetRawConfiguration($expectedConfiguration);

        self::assertEquals('value', $this->context->getOption('foo'));
    }

    public function testGetConfigurationOptionDefault()
    {
        $expectedConfiguration = ['foo' => 'value'];
        $this->expectGetRawConfiguration($expectedConfiguration);

        $this->assertEquals('default', $this->context->getOption('unknown', 'default'));
    }

    private function expectGetRawConfiguration(array $expectedConfiguration, int $count = 1): void
    {
        $jobInstance = $this->createMock(JobInstance::class);

        $jobInstance->expects($this->exactly($count))
            ->method('getRawConfiguration')
            ->willReturn($expectedConfiguration);

        $jobExecution = $this->createMock(JobExecution::class);

        $jobExecution->expects($this->exactly($count))
            ->method('getJobInstance')
            ->willReturn($jobInstance);

        $this->stepExecution->expects($this->exactly($count))
            ->method('getJobExecution')
            ->willReturn($jobExecution);
    }

    public function testAddErrors()
    {
        $messages = ['Error 1', 'Error 2'];

        $this->stepExecution->expects($this->exactly(2))
            ->method('addError')
            ->withConsecutive([$messages[0]], [$messages[1]]);

        $this->context->addErrors($messages);
    }

    public function testGetFailureExceptions()
    {
        $exceptions = [['message' => 'Error 1'], ['message' => 'Error 2']];
        $expected = ['Error 1', 'Error 2'];
        $this->stepExecution->expects($this->once())
            ->method('getFailureExceptions')
            ->willReturn($exceptions);
        $this->assertEquals($expected, $this->context->getFailureExceptions());
    }

    public function testGetWarnings()
    {
        $warning = $this->createMock(Warning::class);
        $expected = new ArrayCollection();
        $expected->add($warning);

        $this->stepExecution->expects($this->once())
            ->method('getWarnings')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->context->getWarnings());
    }
}
