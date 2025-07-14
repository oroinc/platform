<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Exception;

use Oro\Bundle\ActionBundle\Exception\OperationNotFoundException;
use PHPUnit\Framework\TestCase;

class OperationNotFoundExceptionTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new OperationNotFoundException('test_action_name');

        $this->assertEquals('Operation with name "test_action_name" not found', $exception->getMessage());
    }
}
