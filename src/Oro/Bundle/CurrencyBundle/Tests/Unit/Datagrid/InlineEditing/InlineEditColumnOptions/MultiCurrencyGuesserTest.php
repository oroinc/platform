<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Datagrid\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\CurrencyBundle\Converter\CurrencyToString;
use Oro\Bundle\CurrencyBundle\Datagrid\InlineEditing\InlineEditColumnOptions\MultiCurrencyGuesser;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;

class MultiCurrencyGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var CurrencyNameHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyHelper;

    /** @var MultiCurrencyGuesser */
    private $guesser;

    /** @var CurrencyToString */
    private $currencyToStringConverter;

    protected function setUp(): void
    {
        $this->currencyHelper = $this->createMock(CurrencyNameHelper::class);

        $this->guesser = new MultiCurrencyGuesser($this->currencyHelper, $this->getCurrencyToStringConverter());
    }

    private function getCurrencyToStringConverter()
    {
        if (null === $this->currencyToStringConverter) {
            $this->currencyToStringConverter = $this->createMock(CurrencyToString::class);
        }

        return $this->currencyToStringConverter;
    }

    /**
     * @dataProvider setParametersDataProvider
     */
    public function testRelationGuess(array $column, array $expected, bool $isEnabledInline, array $choices)
    {
        if (empty($choices)) {
            $this->currencyHelper->expects($this->never())
                ->method('getCurrencyChoices');
        } else {
            $this->currencyHelper->expects($this->once())
                ->method('getCurrencyChoices')
                ->willReturn($choices);
        }

        $guessed = $this->guesser->guessColumnOptions('test', 'test', $column, $isEnabledInline);
        $this->assertEquals($expected, $guessed);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function setParametersDataProvider(): array
    {
        return [
            'Not applicable column type' => [
                [
                    'frontend_type' => 'test'
                ],
                [],
                true,
                []
            ],
            'Fully configured column' => [
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'orocurrency/js/app/views/editor/multi-currency-editor-view'
                        ],
                        'save_api_accessor' => [
                            'class' => 'orocurrency/js/datagrid/inline-editing/currency-save-api-accessor',
                            'cell_field' => 'test',
                            'value_field'    => 'testValue',
                            'currency_field' => 'testCurrency'
                        ]
                    ],
                    'frontend_type' => 'multi-currency',
                    'type' => 'callback',
                    'callable' => [
                        $this->getCurrencyToStringConverter(),
                        'convert'
                    ],
                    'choices' => [
                        'USD' => 'USD',
                        'UAH' => 'UAH'
                    ]
                ],
                [
                    'inline_editing' => [
                        'enable' => true,
                    ]
                ],
                true,
                []
            ],
            'Without column convertation options' => [
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'orocurrency/js/app/views/editor/multi-currency-editor-view'
                        ],
                        'save_api_accessor' => [
                            'class' => 'orocurrency/js/datagrid/inline-editing/currency-save-api-accessor',
                            'cell_field' => 'test',
                            'value_field'    => 'testValue',
                            'currency_field' => 'testCurrency'
                        ]
                    ],
                    'frontend_type' => 'multi-currency',
                    'type' => 'twig',
                    'choices' => [
                        'USD' => 'USD',
                        'UAH' => 'UAH'
                    ]
                ],
                [
                    'inline_editing' => [
                        'enable' => true,
                    ],
                    'type' => 'callback',
                    'callable' => [
                        $this->getCurrencyToStringConverter(),
                        'convert'
                    ]
                ],
                true,
                []
            ],
            'Without choices' => [
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'orocurrency/js/app/views/editor/multi-currency-editor-view'
                        ],
                        'save_api_accessor' => [
                            'class' => 'orocurrency/js/datagrid/inline-editing/currency-save-api-accessor',
                            'cell_field' => 'test',
                            'value_field'    => 'testValue',
                            'currency_field' => 'testCurrency'
                        ]
                    ],
                    'frontend_type' => 'multi-currency',
                    'type' => 'callback',
                    'callable' => [
                        $this->getCurrencyToStringConverter(),
                        'convert'
                    ],
                ],
                [
                    'inline_editing' => [
                        'enable' => true,
                    ],
                    'choices' => [
                        'USD',
                        'UAH'
                    ]
                ],
                true,
                [
                    'US Dollar' => 'USD',
                    'Ukrainian grivna' => 'UAH',
                ]
            ],
            'Without inline edit options' => [
                [
                    'frontend_type' => 'multi-currency',
                    'choices' => [
                        'USD' => 'USD',
                        'UAH' => 'UAH'
                    ],
                    'type' => 'callback',
                    'callable' => [
                        $this->getCurrencyToStringConverter(),
                        'convert'
                    ],
                    'multicurrency_config' => [
                        'original_field' => 'test',
                        'value_field'    => 'testValue',
                        'currency_field' => 'testCurrency'
                    ]
                ],
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'orocurrency/js/app/views/editor/multi-currency-editor-view'
                        ],
                        'save_api_accessor' => [
                            'class' => 'orocurrency/js/datagrid/inline-editing/currency-save-api-accessor',
                            'cell_field' => 'test',
                            'value_field'    => 'testValue',
                            'currency_field' => 'testCurrency'
                        ],
                        'enable' => true,
                    ]
                ],
                true,
                []
            ]
        ];
    }
}
