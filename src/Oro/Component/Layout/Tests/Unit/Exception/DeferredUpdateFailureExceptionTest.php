<?php

namespace Oro\Component\Layout\Tests\Unit\Exception;

use Oro\Component\Layout\Exception\DeferredUpdateFailureException;

class DeferredUpdateFailureExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $message       = 'Reason of failure.';
        $failedActions = [
            ['name' => 'add', 'args' => ['item1']],
            ['name' => 'remove', 'args' => ['item2']]
        ];

        $exception = new DeferredUpdateFailureException($message, $failedActions);

        $this->assertEquals(
            $message . ' Actions: add(item1), remove(item2).',
            $exception->getMessage()
        );
        $this->assertEquals(
            $failedActions,
            $exception->getFailedActions()
        );
    }
}
