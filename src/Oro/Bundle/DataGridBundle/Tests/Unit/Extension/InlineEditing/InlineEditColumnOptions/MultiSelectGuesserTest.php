<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOption;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\MultiSelectGuesser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Class MultiSelectGuesserTest
 * @package Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOption
 */
class MultiSelectGuesserTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|AclHelper */
    protected $aclHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var MultiSelectGuesser */
    protected $guesser;

    public function setUp()
    {
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->guesser = new MultiSelectGuesser($this->doctrineHelper, $this->aclHelper);
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
//            'not applicable type' => [
//                ['frontend_type' => 'string'],
//                []
//            ],
//            'not fill if configured' => [
//                [
//                    'frontend_type' => 'relation',
//                    'inline_editing' => [
//                        'editor' => [
//                            'view' => 'orodatagrid/js/app/views/editor/related-id-relation-editor-view'
//                        ],
//                        'autocomplete_api_accessor' => [
//                            'class' => 'oroui/js/tools/search-api-accessor'
//                        ]
//                    ]
//                ],
//                []
//            ],
//            'filled if empty' => [
//                ['frontend_type' => 'relation'],
//                [
//                    'inline_editing' => [
//                        'editor' => [
//                            'view' => 'orodatagrid/js/app/views/editor/related-id-relation-editor-view'
//                        ],
//                        'autocomplete_api_accessor' => [
//                            'class' => 'oroui/js/tools/search-api-accessor'
//                        ]
//                    ]
//                ]
//            ],
//            'filled if empty view' => [
//                [
//                    'frontend_type' => 'relation',
//                    'inline_editing' => [
//                        'autocomplete_api_accessor' => [
//                            'class' => 'oroui/js/tools/search-api-accessor'
//                        ]
//                    ]
//                ],
//                [
//                    'inline_editing' => [
//                        'editor' => [
//                            'view' => 'orodatagrid/js/app/views/editor/related-id-relation-editor-view'
//                        ]
//                    ]
//                ]
//            ],
//            'filled if empty accessor' => [
//                [
//                    'frontend_type' => 'relation',
//                    'inline_editing' => [
//                        'editor' => [
//                            'view' => 'orodatagrid/js/app/views/editor/related-id-relation-editor-view'
//                        ]
//                    ]
//                ],
//                [
//                    'inline_editing' => [
//                        'autocomplete_api_accessor' => [
//                            'class' => 'oroui/js/tools/search-api-accessor'
//                        ]
//                    ]
//                ]
//            ],
        ];
    }
}
