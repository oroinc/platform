<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class NotContainsTest extends \PHPUnit\Framework\TestCase
{
    protected $condition;

    public function setUp()
    {
        $this->condition = new Condition\NotContains();
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
            'in_sentence' => [
                'options'        => $options,
                'context'        => ['foo' => 'Here is the word in sentence.', 'bar' => 'word'],
                'expectedResult' => false
            ],
            'at_the_beginning' => [
                'options'        => $options,
                'context'        => ['foo' => 'Word is at the beginning.', 'bar' => 'word'],
                'expectedResult' => false,
            ],
            'at_the_end' => [
                'options'        => $options,
                'context'        => ['foo' => 'At the end is word', 'bar' => 'word'],
                'expectedResult' => false,
            ],
            'case_insensitive' => [
                'options'        => $options,
                'context'        => ['foo' => 'WoRd should match.', 'bar' => 'word'],
                'expectedResult' => false,
            ],
            'special_characters' => [
                'options'        => $options,
                'context'        => ['foo' => 'Příliš žluťoučký', 'bar' => 'žluťoučký'],
                'expectedResult' => false,
            ],
            'missing_word' => [
                'options'        => $options,
                'context'        => ['foo' => 'Příliš žluťoučk', 'bar' => 'žluťoučký'],
                'expectedResult' => true,
            ],
        ];
    }
}
