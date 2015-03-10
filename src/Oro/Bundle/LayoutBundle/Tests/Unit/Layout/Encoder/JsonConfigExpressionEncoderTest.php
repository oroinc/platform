<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Encoder;

use Oro\Component\ConfigExpression\Condition;

use Oro\Bundle\LayoutBundle\Layout\Encoder\JsonConfigExpressionEncoder;

class JsonConfigExpressionEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testEncode()
    {
        $encoder = new JsonConfigExpressionEncoder();
        $result  = $encoder->encode(new Condition\True());
        $this->assertEquals('{"@true":null}', $result);
    }
}
