<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;

class NotInTest extends \PHPUnit_Framework_Testcase
{
    protected $condition;

    public function setUp()
    {
        $this->condition = new Condition\NotIn();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, $context, $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function evaluateDataProvider()
    {
        $options = ['left' => new PropertyPath('foo'), 'right' => new PropertyPath('bar')];

        return [
            'in_array' => [
                'options'        => $options,
                'context'        => ['foo' => 'word', 'bar' => ['sth', 'word', 'sth else']],
                'expectedResult' => false
            ],
            'not_in_array' => [
                'options'        => $options,
                'context'        => ['foo' => 'word', 'bar' => ['sth', 'words', 'sth else']],
                'expectedResult' => true,
            ],
        ];
    }
}
