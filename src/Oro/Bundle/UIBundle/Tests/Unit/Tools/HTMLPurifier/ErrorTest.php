<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools\HTMLPurifier;

use Oro\Bundle\UIBundle\Tools\HTMLPurifier\Error;

class ErrorTest extends \PHPUnit\Framework\TestCase
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
