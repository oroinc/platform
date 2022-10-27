<?php

namespace Oro\Component\Action\Tests\Unit\Event;

use Oro\Component\Action\Event\ExtendableConditionEvent;

class ExtendableConditionEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtendableConditionEvent */
    private $extendableConditionEvent;

    protected function setUp(): void
    {
        $this->extendableConditionEvent = new ExtendableConditionEvent();
    }

    public function testAddError()
    {
        $errorMsg = 'xxx';
        $context = 'yyy';
        $this->extendableConditionEvent->addError($errorMsg, $context);
        $errorResults = $this->extendableConditionEvent->getErrors();
        $this->assertCount(1, $errorResults);

        $this->assertEquals($errorResults[0]['message'], $errorMsg);
        $this->assertEquals($errorResults[0]['context'], $context);
    }

    public function testHasError()
    {
        $this->assertEquals(0, $this->extendableConditionEvent->hasErrors());
        $this->extendableConditionEvent->addError('xxx', 'yyy');
        $this->assertEquals(1, $this->extendableConditionEvent->hasErrors());
    }
}
