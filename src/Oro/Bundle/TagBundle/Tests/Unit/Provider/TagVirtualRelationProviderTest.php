<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Provider;

use Oro\Bundle\TagBundle\Provider\TagVirtualRelationProvider;

class TagVirtualRelationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var TagVirtualRelationProvider */
    protected $provider;

    public function setUp()
    {
        $this->provider = new TagVirtualRelationProvider();
    }

    /**
     * @dataProvider isVirtualRelationProvider
     */
    public function testIsVirtualRelation($class, $field, $expected)
    {
        $this->assertEquals($expected, $this->provider->isVirtualRelation($class, $field));
    }

    /**
     * @return array
     */
    public function isVirtualRelationProvider()
    {
        return [
            ['Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable', 'tags_virtual', true],
            ['Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable', 'another_relation', false],
            ['stdClass', 'tags_virtual', false]
        ];
    }

    /**
     * @dataProvider getVirtualRelationQueryProvider
     */
    public function testGetVirtualRelationQuery($class, $field, $expected)
    {
        $this->assertEquals($expected, $this->provider->getVirtualRelationQuery($class, $field));
    }

    /**
     * @return array
     */
    public function getVirtualRelationQueryProvider()
    {
        return [
            [
                'Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable',
                'tags_virtual',
                [
                    'join' => [
                        'left' => [
                            [
                                'join'          => 'Oro\Bundle\TagBundle\Entity\Tagging',
                                'alias'         => 'virtualTagging',
                                'conditionType' => 'WITH',
                                'condition'     => "(virtualTagging.entityName = "
                                    . "'Oro\\Bundle\\TagBundle\\Tests\\Unit\\Fixtures\\Taggable' and "
                                    . "virtualTagging.recordId = entity.id)"
                            ],
                            [
                                'join'  => 'virtualTagging.tag',
                                'alias' => 'virtualTag'
                            ]
                        ]
                    ]
                ]
            ],
            ['Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable', 'another_relation', []],
            ['stdClass', 'tags_virtual', []]
        ];
    }

    /**
     * @dataProvider getVirtualRelationsProvider
     */
    public function testGetVirtualRelations($class, $expected)
    {
        $this->assertEquals($expected, $this->provider->getVirtualRelations($class));
    }

    public function getVirtualRelationsProvider()
    {
        return [
            [
                'Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable',
                [
                    'tags_virtual' => [
                        'label' => 'oro.tag.entity_plural_label',
                        'relation_type' => 'ManyToMany',
                        'related_entity_name' => 'Oro\Bundle\TagBundle\Entity\Tag',
                        'target_join_alias' => 'virtualTag',
                        'query' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join'          => 'Oro\Bundle\TagBundle\Entity\Tagging',
                                        'alias'         => 'virtualTagging',
                                        'conditionType' => 'WITH',
                                        'condition'     => "(virtualTagging.entityName = "
                                            . "'Oro\\Bundle\\TagBundle\\Tests\\Unit\\Fixtures\\Taggable' and "
                                            . "virtualTagging.recordId = entity.id)"
                                    ],
                                    [
                                        'join'  => 'virtualTagging.tag',
                                        'alias' => 'virtualTag'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            ['stdClass', []]
        ];
    }

    public function testGetTargetJoinAlias()
    {
        $this->assertEquals('virtualTag', $this->provider->getTargetJoinAlias('', ''));
    }
}
