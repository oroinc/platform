<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Condition;

use Oro\Bundle\ActionBundle\Condition\CollectionElementValueExists;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

class CollectionElementValueExistsTest extends \PHPUnit\Framework\TestCase
{
    /** @var CollectionElementValueExists */
    private $condition;

    protected function setUp(): void
    {
        $this->condition = new CollectionElementValueExists();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $exceptionName, string $exceptionMessage)
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);

        $this->condition->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
    {
        return [
            [
                'options' => [],
                'exceptionName' => InvalidArgumentException::class,
                'exceptionMessage' => 'Options must have 2 or more elements, but 0 given.'
            ],
            [
                'options' => ['string', 'string'],
                'exceptionName' => InvalidArgumentException::class,
                'exceptionMessage' => 'Option with index 0 must be property path.'
            ]
        ];
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, array $context, bool $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function evaluateDataProvider(): array
    {
        $options = [new PropertyPath('foo.words'), new PropertyPath('data.name'), new PropertyPath('bar')];

        return [
            'in_array' => [
                'options' => $options,
                'context' => [
                    'foo' => [
                        'sth',
                        'words' => [
                            ['name' => 'worda'],
                            ['name' => 'wordb']
                        ],
                        'sth else'
                    ],
                    'bar' => 'wordb'
                ],
                'expectedResult' => true
            ],
            'not_in_array' => [
                'options' => $options,
                'context' => [
                    'foo' => [
                        'sth',
                        'words' => [
                            ['name' => 'worda'],
                            ['name' => 'wordb']
                        ],
                        'sth else'
                    ],
                    'bar' => 'wordc'
                ],
                'expectedResult' => false,
            ],
            'not_strict_in_array' => [
                'options' => $options,
                'context' => [
                    'foo' => [
                        'sth',
                        'words' => [
                            ['name' => '5'],
                            ['name' => '15']
                        ],
                        'sth else'
                    ],
                    'bar' => 15
                ],
                'expectedResult' => true,
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(CollectionElementValueExists::NAME, $this->condition->getName());
    }
}
