<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition\In;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class InTest extends TestCase
{
    protected $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->condition = new In();
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
                'expectedResult' => true
            ],
            'not_in_array' => [
                'options'        => $options,
                'context'        => ['foo' => 'word', 'bar' => ['sth', 'words', 'sth else']],
                'expectedResult' => false,
            ],
        ];
    }
}
