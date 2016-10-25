<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOption;

use Oro\Bundle\CurrencyBundle\Datagrid\InlineEditing\InlineEditColumnOptions\MultiCurrencyGuesser;

class MultiCurrencyGuesserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyHelper;

    /** @var MultiCurrencyGuesser */
    protected $guesser;

    public function setUp()
    {
        $this->currencyHelper = $this
            ->getMockBuilder('Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->guesser = new MultiCurrencyGuesser($this->currencyHelper);
    }

    /**
     * @param array $column
     * @param array $expected
     *
     * @dataProvider setParametersDataProvider
     */
    public function testRelationGuess($column, $expected)
    {
        $this->currencyHelper->expects($this->once())->method('getCurrencyChoices')
            ->willReturn($expected['choices']);

        $guessed = $this->guesser->guessColumnOptions('test', 'test', $column);
        $expected['choices'] = array_keys($expected['choices']);
        $this->assertEquals($expected, $guessed);
    }

    public function testWrongConfig()
    {
        try {
            $this->currencyHelper->expects($this->once())->method('getCurrencyChoices')
                ->willReturn(['USD' => 'USD']);
            $this->guesser->guessColumnOptions('test', 'test', ['frontend_type' => 'multi-currency']);
            $this->fail('Expected exception not thrown');
        } catch (\LogicException $e) {
             $this->assertEquals(0, $e->getCode());
            $this->assertEquals(
                sprintf(
                    'You need to specify %s for multicurrency column',
                    MultiCurrencyGuesser::MULTI_CURRENCY_CONFIG
                ),
                $e->getMessage()
            );
        }
    }

    public function setParametersDataProvider()
    {
        return [
            'empty' => [
                [
                    'frontend_type' => 'multi-currency',
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
                        ]
                    ],
                    'frontend_type' => 'multi-currency',
                    'type' => 'callback',
                    'choices' => [
                        'USD' => 'USD',
                        'UAH' => 'UAH'
                    ],
                    'params' => [
                        'value' => 'test',
                        'currency' => 'testCurrency'
                    ]
                ],
                true
            ],
            'incorrect type fix' => [
                [
                    'frontend_type' => 'multi-currency',
                    'type' => 'field',
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
                        ]
                    ],
                    'frontend_type' => 'multi-currency',
                    'type' => 'callback',
                    'choices' => [
                        'USD' => 'USD',
                        'UAH' => 'UAH'
                    ],
                    'params' => [
                        'value' => 'test',
                        'currency' => 'testCurrency'
                    ]
                ],
                true
            ]
        ];
    }
}
