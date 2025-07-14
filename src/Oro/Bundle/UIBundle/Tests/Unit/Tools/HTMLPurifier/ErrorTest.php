<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools\HTMLPurifier;

use Oro\Bundle\UIBundle\Tools\HTMLPurifier\Error;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    public function testGetters(): void
    {
        $message = 'test message';
        $place = 'test place';

        $error = new Error($message, $place);

        $this->assertEquals($message, $error->getMessage());
        $this->assertEquals($place, $error->getPlace());
    }
}
