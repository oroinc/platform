<?php

namespace Oro\Component\Layout\Tests\Unit\Exception;

use Oro\Component\Layout\Exception\DeferredUpdateFailureException;

class DeferredUpdateFailureExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $message       = 'Reason of failure.';
        $failedActions = [
            ['name' => 'add', 'args' => ['item1', 'parent1']],
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

    public function testConstructorWithActionArgsToStringCallback()
    {
        $message       = 'Reason of failure.';
        $failedActions = [
            ['name' => 'add', 'args' => ['item1', 'parent1']],
            ['name' => 'remove', 'args' => ['item2']]
        ];

        $exception = new DeferredUpdateFailureException(
            $message,
            $failedActions,
            [$this, 'actionArgsToString']
        );

        $this->assertEquals(
            $message . ' Actions: add(item1, parent1), remove(item2).',
            $exception->getMessage()
        );
        $this->assertEquals(
            $failedActions,
            $exception->getFailedActions()
        );
    }

    public function actionArgsToString($action)
    {
        if ($action['name'] === 'add') {
            return sprintf('%s, %s', $action['args'][0], $action['args'][1]);
        }

        return null;
    }
}
