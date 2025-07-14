<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Exception;

use Oro\Bundle\ActionBundle\Exception\ActionGroupNotFoundException;
use PHPUnit\Framework\TestCase;

class ActionGroupNotFoundExceptionTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new ActionGroupNotFoundException('test_name');

        $this->assertEquals('ActionGroup with name "test_name" not found', $exception->getMessage());
    }
}
