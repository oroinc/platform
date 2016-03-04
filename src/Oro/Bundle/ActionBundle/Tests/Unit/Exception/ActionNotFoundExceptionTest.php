<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Exception;

use Oro\Bundle\ActionBundle\Exception\ActionNotFoundException;

class ActionNotFoundExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $exception = new ActionNotFoundException('test_action_name');

        $this->assertEquals('Action with name "test_action_name" not found', $exception->getMessage());
    }
}
