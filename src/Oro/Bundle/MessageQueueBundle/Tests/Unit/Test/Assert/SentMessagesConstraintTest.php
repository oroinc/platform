<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Test\Assert;

use Oro\Bundle\MessageQueueBundle\Test\Assert\SentMessagesConstraint;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SentMessagesConstraintTest extends TestCase
{
    public function testShouldBeEvaluatedToFalseIfValueIsNotEqualToExpectedMessages(): void
    {
        $expectedMessages = [
            ['topic' => 'test topic', 'message' => 'test message']
        ];
        $allMessages = [
            ['topic' => 'test topic', 'message' => 'test message 1'],
            ['topic' => 'test topic 1', 'message' => 'test message'],
        ];

        $constraint = new SentMessagesConstraint($expectedMessages);
        $this->assertFalse($constraint->evaluate($allMessages, '', true));
    }

    public function testShouldBeEvaluatedToTrueIfValueEqualsToExpectedMessage(): void
    {
        $expectedMessages = [
            ['topic' => 'test topic', 'message' => 'test message 1'],
            ['topic' => 'test topic', 'message' => 'test message'],
        ];
        $allMessages = [
            ['topic' => 'test topic', 'message' => 'test message 1'],
            ['topic' => 'test topic', 'message' => 'test message'],
        ];

        $constraint = new SentMessagesConstraint($expectedMessages);
        $this->assertTrue($constraint->evaluate($allMessages, '', true));
    }

    public function testShouldThrowExceptionWithValidMessage(): void
    {
        $expectedMessages = [
            ['topic' => 'test topic', 'message' => 'test message']
        ];
        $allMessages = [
            ['topic' => 'test topic', 'message' => 'test message 1'],
            ['topic' => 'test topic 1', 'message' => 'test message'],
        ];
        $expectedExceptionMessage = <<<TEXT
additional description
Failed asserting that exactly all messages were sent.
--- Expected
+++ Actual
@@ @@
 Array (
     0 => Array (
         'topic' => 'test topic'
-        'message' => 'test message'
+        'message' => 'test message 1'
     )
+    1 => Array (...)
 )

TEXT;

        $constraint = new SentMessagesConstraint($expectedMessages);
        try {
            $constraint->evaluate($allMessages, 'additional description');
        } catch (ExpectationFailedException $e) {
            self::assertEquals(
                $expectedExceptionMessage,
                $e->getMessage() . $e->getComparisonFailure()->getDiff()
            );
        }
    }
}
