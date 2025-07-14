<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class StrategyValidationEventTest extends TestCase
{
    public function testViolations(): void
    {
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $event = new StrategyValidationEvent($violations);
        $this->assertSame($violations, $event->getConstraintViolationList());
    }

    public function testAddError(): void
    {
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $event = new StrategyValidationEvent($violations);
        $event->addError('test error');
        $this->assertSame(['test error'], $event->getErrors());
    }

    public function testRemoveError(): void
    {
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $event = new StrategyValidationEvent($violations);
        $event->addError('test error');
        $event->removeError('test error');
        $this->assertSame([], $event->getErrors());
    }

    public function testName(): void
    {
        $this->assertIsString(StrategyValidationEvent::BUILD_ERRORS);
    }

    public function testDelimiter(): void
    {
        $this->assertIsString(StrategyValidationEvent::DELIMITER);
    }
}
