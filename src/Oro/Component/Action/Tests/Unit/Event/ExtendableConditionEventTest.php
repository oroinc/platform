<?php

namespace Oro\Component\Action\Tests\Unit\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Model\AbstractStorage;
use PHPUnit\Framework\TestCase;

class ExtendableConditionEventTest extends TestCase
{
    public function testGetContextWithNull(): void
    {
        $event = new ExtendableConditionEvent();
        $this->assertNull($event->getContext());
    }

    public function testGetContextWithNonNullValue(): void
    {
        $context = $this->createMock(AbstractStorage::class);
        $event = new ExtendableConditionEvent($context);

        $this->assertSame($context, $event->getContext());
    }

    public function testAddError(): void
    {
        $event = new ExtendableConditionEvent();
        $errorMessage = 'Error occurred';
        $errorContext = ['key' => 'value'];

        $event->addError($errorMessage, $errorContext);

        $this->assertTrue($event->hasErrors());

        $errors = $event->getErrors();
        $this->assertInstanceOf(ArrayCollection::class, $errors);
        $this->assertCount(1, $errors);

        $expectedError = ['message' => $errorMessage, 'context' => $errorContext];
        $this->assertSame($expectedError, $errors->first());
    }

    public function testHasErrors(): void
    {
        $event = new ExtendableConditionEvent();

        $this->assertFalse($event->hasErrors());

        $event->addError('Error message');
        $this->assertTrue($event->hasErrors());
    }
}
