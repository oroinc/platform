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

    public function setParametersDataProvider()
    {
        return [
            'empty' => [
                [
                    'frontend_type' => 'multi-currency',
                ],
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'orocurrency/js/app/views/editor/multi-currency-editor-view'
                        ],
                        'save_api_accessor' => [
                            'class' => 'orocurrency/js/datagrid/inline-editing/currency-save-api-accessor',
                            'cell_field' => 'test'
                        ]
                    ],
                    'frontend_type' => 'multi-currency',
                    'type' => 'callback',
                    'choices' => [
                        'USD' => 'USD',
                        'UAH' => 'UAH'
                    ]
                ],
                true
            ],
            'incorrect type fix' => [
                [
                    'frontend_type' => 'multi-currency',
                    'type' => 'field',
                ],
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'orocurrency/js/app/views/editor/multi-currency-editor-view'
                        ],
                        'save_api_accessor' => [
                            'class' => 'orocurrency/js/datagrid/inline-editing/currency-save-api-accessor',
                            'cell_field' => 'test'
                        ]
                    ],
                    'frontend_type' => 'multi-currency',
                    'type' => 'callback',
                    'choices' => [
                        'USD' => 'USD',
                        'UAH' => 'UAH'
                    ]
                ],
                true
            ]
        ];
    }
}
