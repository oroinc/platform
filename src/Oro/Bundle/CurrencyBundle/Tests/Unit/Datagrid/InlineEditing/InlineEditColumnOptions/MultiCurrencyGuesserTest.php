<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOption;

use Oro\Bundle\CurrencyBundle\Converter\CurrencyToString;
use Oro\Bundle\CurrencyBundle\Datagrid\InlineEditing\InlineEditColumnOptions\MultiCurrencyGuesser;

class MultiCurrencyGuesserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $currencyHelper;

    /** @var MultiCurrencyGuesser */
    protected $guesser;

    /** @var CurrencyToString */
    protected $currencyToStringConverter;

    public function setUp()
    {
        $this->currencyHelper = $this
            ->getMockBuilder('Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->guesser = new MultiCurrencyGuesser($this->currencyHelper, $this->getCurrencyToStringConverter());
    }

    protected function getCurrencyToStringConverter()
    {
        if (null === $this->currencyToStringConverter) {
            $this->currencyToStringConverter = $this
                ->getMockBuilder('Oro\Bundle\CurrencyBundle\Converter\CurrencyToString')
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->currencyToStringConverter;
    }

    /**
     * @param array $column
     * @param array $expected
     * @param bool  $isEnabledInline
     * @param array $choices
     *
     * @dataProvider setParametersDataProvider
     */
    public function testRelationGuess($column, $expected, $isEnabledInline, $choices)
    {
        if (empty($choices)) {
            $this
                ->currencyHelper
                ->expects($this->never())
                ->method('getCurrencyChoices');
        } else {
            $this
                ->currencyHelper
                ->expects($this->once())
                ->method('getCurrencyChoices')
                ->willReturn($choices);
        }

        $guessed = $this->guesser->guessColumnOptions('test', 'test', $column, $isEnabledInline);
        $this->assertEquals($expected, $guessed);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function setParametersDataProvider()
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
