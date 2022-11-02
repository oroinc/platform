<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\MultiRelationGuesser;

class MultiRelationGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var MultiRelationGuesser */
    private $guesser;

    protected function setUp(): void
    {
        $this->guesser = new MultiRelationGuesser();
    }

    /**
     * @dataProvider setParametersDataProvider
     */
    public function testRelationGuess(array $column, array $expected)
    {
        $guessed = $this->guesser->guessColumnOptions('test', 'test', $column);

        $this->assertEquals($expected, $guessed);
    }

    public function setParametersDataProvider(): array
    {
        return [
            'empty' => [
                [],
                []
            ],
            'not applicable type' => [
                ['frontend_type' => 'string'],
                []
            ],
            'not fill if configured' => [
                [
                    'frontend_type' => 'multi-relation',
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'oroform/js/app/views/editor/multi-relation-editor-view'
                        ],
                        'autocomplete_api_accessor' => [
                            'class' => 'oroui/js/tools/search-api-accessor'
                        ]
                    ]
                ],
                []
            ],
            'filled if empty' => [
                ['frontend_type' => 'multi-relation'],
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'oroform/js/app/views/editor/multi-relation-editor-view'
                        ],
                        'autocomplete_api_accessor' => [
                            'class' => 'oroui/js/tools/search-api-accessor'
                        ]
                    ]
                ]
            ],
            'filled if empty view' => [
                [
                    'frontend_type' => 'multi-relation',
                    'inline_editing' => [
                        'autocomplete_api_accessor' => [
                            'class' => 'oroui/js/tools/search-api-accessor'
                        ]
                    ]
                ],
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'oroform/js/app/views/editor/multi-relation-editor-view'
                        ]
                    ]
                ]
            ],
            'filled if empty accessor' => [
                [
                    'frontend_type' => 'multi-relation',
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'oroform/js/app/views/editor/multi-relation-editor-view'
                        ]
                    ]
                ],
                [
                    'inline_editing' => [
                        'autocomplete_api_accessor' => [
                            'class' => 'oroui/js/tools/search-api-accessor'
                        ]
                    ]
                ]
            ],
        ];
    }
}
