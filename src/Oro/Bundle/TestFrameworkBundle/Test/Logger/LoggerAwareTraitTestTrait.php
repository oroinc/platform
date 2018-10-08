<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Logger;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Use this test trait for testing classes that use LoggerAwareTrait
 * Please, start by calling $this->setUpLoggerMock() method in the setUp() method of your testCase class.
 * Then use assertions of this trait in the appropriate test methods.
 */
trait LoggerAwareTraitTestTrait
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected $loggerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerAwareInterface
     */
    protected $loggerAwareObject;

    public function testSetLogger()
    {
        $this->assertNotNull(
            $this->loggerAwareObject,
            'LoggerAwareObject instance is null. May you have forgotten
             to call $this->setUpLoggerMock() in $this->setUp() method?'
        );

        $loggerAwareObjectReflection = new \ReflectionClass(get_class($this->loggerAwareObject));

        $this->assertTrue($loggerAwareObjectReflection->hasProperty('logger'));

        $loggerPropertyReflection = new \ReflectionProperty(
            get_class($this->loggerAwareObject),
            'logger'
        );
        $loggerPropertyReflection->setAccessible(true);

        $this->assertInstanceOf(LoggerInterface::class, $this->loggerMock);

        $this->assertSame(
            $this->loggerMock,
            $loggerPropertyReflection->getValue($this->loggerAwareObject)
        );
    }

    /**
     * Be sure to call this method in the setUp() method of the testCase class
     * @param LoggerAwareInterface $testedObject
     */
    protected function setUpLoggerMock(LoggerAwareInterface $testedObject)
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->loggerAwareObject = $testedObject;
        $this->loggerAwareObject->setLogger($this->loggerMock);
    }

    protected function assertLoggerCalled()
    {
        $this->loggerMock->expects($this->atLeast(1))->method($this->anything());
    }

    protected function assertLoggerNotCalled()
    {
        $this->loggerMock->expects($this->never())->method($this->anything());
    }

    protected function assertLoggerEmergencyMethodCalled()
    {
        $this->loggerMock->expects($this->atLeast(1))->method('emergency');
    }

    protected function assertLoggerAlertMethodCalled()
    {
        $this->loggerMock->expects($this->atLeast(1))->method('alert');
    }

    protected function assertLoggerCriticalMethodCalled()
    {
        $this->loggerMock->expects($this->atLeast(1))->method('critical');
    }

    protected function assertLoggerErrorMethodCalled()
    {
        $this->loggerMock->expects($this->atLeast(1))->method('error');
    }

    protected function assertLoggerWarningMethodCalled()
    {
        $this->loggerMock->expects($this->atLeast(1))->method('warning');
    }

    protected function assertLoggerNoticeMethodCalled()
    {
        $this->loggerMock->expects($this->atLeast(1))->method('notice');
    }

    protected function assertLoggerInfoMethodCalled()
    {
        $this->loggerMock->expects($this->atLeast(1))->method('info');
    }

    protected function assertLoggerDebugMethodCalled()
    {
        $this->loggerMock->expects($this->atLeast(1))->method('debug');
    }

    protected function assertLoggerLogMethodCalled()
    {
        $this->loggerMock->expects($this->atLeast(1))->method('log');
    }
}
