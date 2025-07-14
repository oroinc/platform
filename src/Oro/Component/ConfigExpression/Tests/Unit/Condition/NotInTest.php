<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition\NotIn;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\Testcase;
use Symfony\Component\PropertyAccess\PropertyPath;

class NotInTest extends Testcase
{
    protected $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->condition = new NotIn();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, $context, $expectedResult): void
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function evaluateDataProvider(): array
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
