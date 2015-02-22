<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Component\ConfigExpression\Condition;

use Oro\Bundle\LayoutBundle\Layout\Extension\JsonConfigExpressionEncoder;

class JsonConfigExpressionEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testEncode()
    {
        $encoder = new JsonConfigExpressionEncoder();
        $result  = $encoder->encode(new Condition\True());
        $this->assertEquals('{"@true":null}', $result);
    }
}
