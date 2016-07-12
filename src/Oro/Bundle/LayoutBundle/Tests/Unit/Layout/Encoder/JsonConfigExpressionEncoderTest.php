<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Encoder;

use Symfony\Component\ExpressionLanguage\Node\BinaryNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\UnaryNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\Layout\Action;

use Oro\Bundle\LayoutBundle\Layout\Encoder\JsonConfigExpressionEncoder;

class JsonConfigExpressionEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodeExpr()
    {
        $parsedExpression = $this->createParsedExpression();

        $encoder = new JsonConfigExpressionEncoder();
        $result = $encoder->encodeExpr($parsedExpression);
        $this->assertJsonStringEqualsJsonFile(__DIR__.'/expression.json', $result);
    }

    public function testEncodeActions()
    {
        $encoder = new JsonConfigExpressionEncoder();
        $result = $encoder->encodeActions(
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

    /**
     * @return ParsedExpression
     */
    protected function createParsedExpression()
    {
        $node = new BinaryNode('===', new UnaryNode('!', new ConstantNode(true)), new ConstantNode(false));
        $parsedExpression = new ParsedExpression('!true === false', $node);

        return $parsedExpression;
    }
}
