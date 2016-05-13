<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Encoder;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\Layout\Action;

use Oro\Bundle\LayoutBundle\Layout\Encoder\JsonConfigExpressionEncoder;

class JsonConfigExpressionEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodeExpr()
    {
        $encoder = new JsonConfigExpressionEncoder();
        $result  = $encoder->encodeExpr(new Condition\TrueCondition());
        $this->assertEquals('{"@true":null}', $result);
    }

    public function testEncodeActions()
    {
        $encoder = new JsonConfigExpressionEncoder();
        $result  = $encoder->encodeActions(
            [
                new Action('add', ['val1']),
                new Action('remove', ['val2'])
            ]
        );
        $this->assertEquals(
            '{"@actions":[{"name":"add","args":["val1"]},{"name":"remove","args":["val2"]}]}',
            $result
        );
    }
}
