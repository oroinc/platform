<?php

namespace Oro\Component\ConfigExpression\Tests\Integration;

use Oro\Component\ConfigExpression\ConfigExpressions;
use Oro\Component\ConfigExpression\ExpressionAssembler;

class ExpressionAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionAssembler */
    private $expressionAssembler;

    protected function setUp(): void
    {
        $configExpressions = new ConfigExpressions();
        $this->expressionAssembler = $configExpressions->getAssembler();
    }

    /**
     * @dataProvider assembleDataProvider
     */
    public function testAssemble(array $configuration)
    {
        $expr = $this->expressionAssembler->assemble($configuration);
        $this->assertEquals($configuration, $expr->toArray());
    }

    public function assembleDataProvider(): array
    {
        return [
            [
                [
                    '@and' => [
                        'parameters' => [
                            [
                                '@or' => [
                                    'parameters' => [
                                        [
                                            '@empty' => [
                                                'parameters' => [
                                                    '$field',
                                                ],
                                            ],
                                        ],
                                        [
                                            '@contains' => [
                                                'parameters' => [
                                                    '$field2', 'value'
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    '@and' => [
                        'parameters' => [
                            [
                                '@or' => [
                                    'parameters' => [
                                        [
                                            '@empty' => [
                                                'parameters' => [
                                                    '$field'
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                '@or' => [
                                    'parameters' => [
                                        [
                                            '@contains' => [
                                                'parameters' => [
                                                    '$field2', 'value'
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
