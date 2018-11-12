<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsParser;

/**
 * Tests for ExtendOptionsParser and ExtendOptionsBuilder
 */
class ExtendOptionsParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityMetadataHelper;

    /** @var ExtendOptionsParser */
    protected $extendOptionsParser;

    protected function setUp()
    {
        $this->entityMetadataHelper =
            $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper')
                ->disableOriginalConstructor()
                ->getMock();

        $this->entityMetadataHelper->expects($this->any())
            ->method('getEntityClassesByTableName')
            ->willReturnMap(
                [
                    ['table1', ['Test\Entity1']],
                    ['table2', ['Test\Entity2']],
                ]
            );

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnValue(true));
        $this->extendOptionsParser = new ExtendOptionsParser(
            $this->entityMetadataHelper,
            new FieldTypeHelper(['enum' => 'manyToOne', 'multiEnum' => 'manyToMany']),
            $configManager
        );
    }

    /**
     * @dataProvider parseOptionsProvider
     */
    public function testParseOptions($options, $expected)
    {
        $result = $this->extendOptionsParser->parseOptions($options);
        $this->assertEquals($expected, $result);
    }

    public function parseOptionsProvider()
    {
        return [
            [
                'options'  => [],
                'expected' => []
            ],
            [
                'options'  => [
                    'table1'         => [
                        '_mode'  => 'hidden',
                        'scope1' => [
                            'attr1' => 'value1',
                            'attr2' => [
                                'key21' => 'value21'
                            ]
                        ]
                    ],
                    'table1!column1' => [
                        '_mode'  => 'hidden',
                        'scope2' => [
                            'field_attr1' => 'field_value1',
                            'field_attr2' => [
                                'field_key21' => 'field_value21'
                            ]
                        ]
                    ],
                    '_append'        => [
                        'table1'         => [
                            'scope1' => [
                                'attr11' => 'value11'
                            ]
                        ],
                        'table1!column1' => [
                            'scope1' => [
                                'field_attr111' => 'field_value111'
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    'Test\Entity1'    => [
                        'mode'    => 'hidden',
                        'configs' => [
                            'scope1' => [
                                'attr1' => 'value1',
                                'attr2' => [
                                    'key21' => 'value21'
                                ]
                            ]
                        ],
                        'fields'  => [
                            'column1' => [
                                'mode'    => 'hidden',
                                'configs' => [
                                    'scope2' => [
                                        'field_attr1' => 'field_value1',
                                        'field_attr2' => [
                                            'field_key21' => 'field_value21'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '_append_configs' => [
                        'Test\Entity1' => [
                            'configs' => [
                                'scope1' => [
                                    'attr11' => 'value11'
                                ]
                            ],
                            'fields'  => [
                                'column1' => [
                                    'scope1' => [
                                        'field_attr111' => 'field_value111'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
