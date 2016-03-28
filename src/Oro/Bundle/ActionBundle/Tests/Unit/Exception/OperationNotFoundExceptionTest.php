<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Exception;

use Oro\Bundle\ActionBundle\Exception\OperationNotFoundException;

class OperationFoundExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $exception = new OperationNotFoundException('test_action_name');

        $this->assertEquals('Operation with name "test_action_name" not found', $exception->getMessage());
    }
}
