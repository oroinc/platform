<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Exception;

use Oro\Bundle\ActionBundle\Exception\ActionGroupNotFoundException;

class ActionGroupNotFoundExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $exception = new ActionGroupNotFoundException('test_name');

        $this->assertEquals('ActionGroup with name "test_name" not found', $exception->getMessage());
    }
}
