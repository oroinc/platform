<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;

class StepExecutionProxyContextTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $stepExecution;

    /**
     * @var StepExecutionProxyContext
     */
    protected $context;

    protected function setUp()
    {
        $this->stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
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
        $expected = array('Error message');

        $this->stepExecution->expects($this->once())
            ->method('getErrors')
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $this->context->getErrors());
    }

    public function testIncrementReadCount()
    {
        $this->stepExecution->expects($this->once())
            ->method('setReadCount')
            ->with(2);
        $this->stepExecution
            ->expects(self::exactly(3))
            ->method('getReadCount')
            ->will($this->returnValue(1));

        $jobInstance = $this->createMock(JobInstance::class);
        $jobInstance
            ->expects(self::exactly(2))
            ->method('getRawConfiguration')
            ->willReturnOnConsecutiveCalls(['incremented_read' => false], ['incremented_read' => true]);

        $jobExecution = $this->createMock(JobExecution::class);
        $jobExecution
            ->expects(self::exactly(2))
            ->method('getJobInstance')
            ->willReturn($jobInstance);

        $this->stepExecution
            ->expects(self::exactly(2))
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
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $this->context->getReadCount());
    }

    /**
     * @dataProvider incrementCountDataProvider
     */
    public function testIncrement($propertyName)
    {
        $expectedCount = 1;

        $executionContext = $this->createMock('Akeneo\Bundle\BatchBundle\Item\ExecutionContext');

        $this->stepExecution->expects($this->exactly(2))
            ->method('getExecutionContext')
            ->will($this->returnValue($executionContext));

        $executionContext->expects($this->at(0))
            ->method('get')
            ->with($propertyName)
            ->will($this->returnValue($expectedCount));

        $executionContext->expects($this->at(1))
            ->method('put')
            ->with($propertyName, $expectedCount + 1);

        $method = 'increment' . str_replace('_', '', $propertyName);
        $this->context->$method();
    }

    public function incrementCountDataProvider()
    {
        return array(
            array('read_offset'),
            array('update_count'),
            array('replace_count'),
            array('delete_count'),
            array('error_entries_count'),
            array('add_count')
        );
    }

    /**
     * @dataProvider getCountDataProvider
     */
    public function testGetCount($propertyName)
    {
        $expectedCount = 1;

        $executionContext = $this->createMock('Akeneo\Bundle\BatchBundle\Item\ExecutionContext');

        $this->stepExecution->expects($this->once())
            ->method('getExecutionContext')
            ->will($this->returnValue($executionContext));

        $executionContext->expects($this->once())
            ->method('get')
            ->with($propertyName)
            ->will($this->returnValue($expectedCount));

        $method = 'get' . str_replace('_', '', $propertyName);
        $this->assertEquals($expectedCount, $this->context->$method());
    }

    public function getCountDataProvider()
    {
        return array(
            array('read_offset'),
            array('update_count'),
            array('replace_count'),
            array('delete_count'),
            array('error_entries_count'),
            array('add_count')
        );
    }

    public function testGetConfiguration()
    {
        $expectedConfiguration = array('foo' => 'value');
        $this->expectGetRawConfiguration($expectedConfiguration);
        $this->assertSame($expectedConfiguration, $this->context->getConfiguration());
    }

    public function testHasConfigurationOption()
    {
        $expectedConfiguration = array('foo' => 'value');
        $this->expectGetRawConfiguration($expectedConfiguration);

        $this->assertTrue($this->context->hasOption('foo'));
    }

    public function testHasConfigurationOptionUnknown()
    {
        $expectedConfiguration = array('foo' => 'value');
        $this->expectGetRawConfiguration($expectedConfiguration);

        $this->assertFalse($this->context->hasOption('unknown'));
    }

    public function testGetConfigurationOption()
    {
        $expectedConfiguration = array('foo' => 'value');
        $this->expectGetRawConfiguration($expectedConfiguration);

        self::assertEquals('value', $this->context->getOption('foo'));
    }

    public function testGetConfigurationOptionDefault()
    {
        $expectedConfiguration = array('foo' => 'value');
        $this->expectGetRawConfiguration($expectedConfiguration);

        $this->assertEquals('default', $this->context->getOption('unknown', 'default'));
    }

    protected function expectGetRawConfiguration(array $expectedConfiguration, $count = 1)
    {
        $jobInstance = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobInstance')
            ->disableOriginalConstructor()
            ->getMock();

        $jobInstance->expects($this->exactly($count))->method('getRawConfiguration')
            ->will($this->returnValue($expectedConfiguration));

        $jobExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $jobExecution->expects($this->exactly($count))->method('getJobInstance')
            ->will($this->returnValue($jobInstance));

        $this->stepExecution->expects($this->exactly($count))->method('getJobExecution')
            ->will($this->returnValue($jobExecution));
    }

    public function testAddErrors()
    {
        $messages = array('Error 1', 'Error 2');

        $this->stepExecution->expects($this->exactly(2))
            ->method('addError');
        $this->stepExecution->expects($this->at(0))
            ->method('addError')
            ->with($messages[0]);
        $this->stepExecution->expects($this->at(1))
            ->method('addError')
            ->with($messages[1]);

        $this->context->addErrors($messages);
    }

    public function testGetFailureExceptions()
    {
        $exceptions = array(array('message' => 'Error 1'), array('message' => 'Error 2'));
        $expected = array('Error 1', 'Error 2');
        $this->stepExecution->expects($this->once())
            ->method('getFailureExceptions')
            ->will($this->returnValue($exceptions));
        $this->assertEquals($expected, $this->context->getFailureExceptions());
    }

    public function testGetWarnings()
    {
        $warning =
            $this
                ->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\Warning')
                ->disableOriginalConstructor()
                ->getMock();
        $expected = new ArrayCollection();
        $expected->add($warning);

        $this->stepExecution->expects($this->once())
            ->method('getWarnings')
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $this->context->getWarnings());
    }
}
