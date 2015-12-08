<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOption;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\RelationGuesser;

/**
 * Class RelationGuesserTest
 * @package Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOption
 */
class RelationGuesserTest extends \PHPUnit_Framework_TestCase
{
    /** @var RelationGuesser */
    protected $guesser;

    public function setUp()
    {
        $this->guesser = new RelationGuesser();
    }

    /**
     * @param array $column
     * @param array $expected
     *
     * @dataProvider setParametersDataProvider
     */
    public function testRelationGuess($column, $expected)
    {
        $guessed = $this->guesser->guessColumnOptions('test', 'test', $column);

        $this->assertEquals($expected, $guessed);
    }

    public function setParametersDataProvider()
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
                    'frontend_type' => 'relation',
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'orodatagrid/js/app/views/editor/related-id-relation-editor-view'
                        ],
                        'autocomplete_api_accessor' => [
                            'class' => 'oroui/js/tools/search-api-accessor'
                        ]
                    ]
                ],
                []
            ],
            'filled if empty' => [
                ['frontend_type' => 'relation'],
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'orodatagrid/js/app/views/editor/related-id-relation-editor-view'
                        ],
                        'autocomplete_api_accessor' => [
                            'class' => 'oroui/js/tools/search-api-accessor'
                        ]
                    ]
                ]
            ],
            'filled if empty view' => [
                [
                    'frontend_type' => 'relation',
                    'inline_editing' => [
                        'autocomplete_api_accessor' => [
                            'class' => 'oroui/js/tools/search-api-accessor'
                        ]
                    ]
                ],
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'orodatagrid/js/app/views/editor/related-id-relation-editor-view'
                        ]
                    ]
                ]
            ],
            'filled if empty accessor' => [
                [
                    'frontend_type' => 'relation',
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'orodatagrid/js/app/views/editor/related-id-relation-editor-view'
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
