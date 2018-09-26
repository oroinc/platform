<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultActionProcessor;
use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultContext;
use Oro\Bundle\ApiBundle\Processor\StepExecutor;

class StepExecutorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $processor;

    /** @var StepExecutor */
    private $stepExecutor;

    protected function setUp()
    {
        $this->processor = $this->createMock(ByStepNormalizeResultActionProcessor::class);

        $this->stepExecutor = new StepExecutor($this->processor);
    }

    public function testExecuteStepNoResetErrors()
    {
        $stepName = 'testStep';
        $context = new ByStepNormalizeResultContext();
        $context->addError(Error::create('some error'));

        $this->processor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($stepName) {
                self::assertEquals($stepName, $context->getFirstGroup());
                self::assertEquals($stepName, $context->getLastGroup());
                self::assertTrue($context->hasErrors());
            });

        $this->stepExecutor->executeStep($stepName, $context, false);
        self::assertTrue($context->hasErrors());
    }

    public function testExecuteStepWithResetErrors()
    {
        $stepName = 'testStep';
        $context = new ByStepNormalizeResultContext();
        $context->addError(Error::create('some error'));

        $this->processor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($stepName) {
                self::assertEquals($stepName, $context->getFirstGroup());
                self::assertEquals($stepName, $context->getLastGroup());
                self::assertFalse($context->hasErrors());
            });

        $this->stepExecutor->executeStep($stepName, $context);
        self::assertTrue($context->hasErrors());
    }

    public function testExecuteStepWithResetErrorsAndNewErrorsOccurred()
    {
        $stepName = 'testStep';
        $context = new ByStepNormalizeResultContext();
        $context->addError(Error::create('some error1'));

        $this->processor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($stepName) {
                self::assertEquals($stepName, $context->getFirstGroup());
                self::assertEquals($stepName, $context->getLastGroup());
                self::assertFalse($context->hasErrors());
                $context->addError(Error::create('some error2'));
            });

        $this->stepExecutor->executeStep($stepName, $context);
        $errors = $context->getErrors();
        self::assertCount(2, $errors);
        self::assertEquals('some error1', $errors[0]->getTitle());
        self::assertEquals('some error2', $errors[1]->getTitle());
    }

    public function testExecuteStepWithResetErrorsAndNoExistingErrorsButNewErrorsOccurred()
    {
        $stepName = 'testStep';
        $context = new ByStepNormalizeResultContext();

        $this->processor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($stepName) {
                self::assertEquals($stepName, $context->getFirstGroup());
                self::assertEquals($stepName, $context->getLastGroup());
                self::assertFalse($context->hasErrors());
                $context->addError(Error::create('some error'));
            });

        $this->stepExecutor->executeStep($stepName, $context);
        $errors = $context->getErrors();
        self::assertCount(1, $errors);
        self::assertEquals('some error', $errors[0]->getTitle());
    }
}
