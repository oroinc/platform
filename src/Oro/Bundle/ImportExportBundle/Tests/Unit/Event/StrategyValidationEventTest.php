<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class StrategyValidationEventTest extends \PHPUnit\Framework\TestCase
{
    public function testViolations()
    {
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $event = new StrategyValidationEvent($violations);
        $this->assertSame($violations, $event->getConstraintViolationList());
    }

    public function testAddError()
    {
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $event = new StrategyValidationEvent($violations);
        $event->addError('test error');
        $this->assertSame(['test error'], $event->getErrors());
    }

    public function testRemoveError()
    {
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $event = new StrategyValidationEvent($violations);
        $event->addError('test error');
        $event->removeError('test error');
        $this->assertSame([], $event->getErrors());
    }

    public function testName()
    {
        $this->assertIsString(StrategyValidationEvent::BUILD_ERRORS);
    }

    public function testDelimiter()
    {
        $this->assertIsString(StrategyValidationEvent::DELIMITER);
    }
}
