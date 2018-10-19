<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Provider;

use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Provider\TagVirtualRelationProvider;

class TagVirtualRelationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TagVirtualRelationProvider */
    protected $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TaggableHelper */
    protected $taggableHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TaggableHelper */
    protected $doctrineHelper;

    public function setUp()
    {
        $this->taggableHelper = $this->getMockBuilder('Oro\Bundle\TagBundle\Helper\TaggableHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new TagVirtualRelationProvider(
            $this->taggableHelper,
            $this->doctrineHelper
        );
    }

    /**
     * @dataProvider isVirtualRelationProvider
     */
    public function testIsVirtualRelation($class, $field, $isTaggable, $expected)
    {
        $this->setHelperExpectation($isTaggable);
        $this->assertEquals($expected, $this->provider->isVirtualRelation($class, $field));
    }

    /**
     * @return array
     */
    public function isVirtualRelationProvider()
    {
        return [
            ['Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable', 'tags_virtual', true, true],
            ['Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable', 'another_relation', true, false],
            ['stdClass', 'tags_virtual', false, false]
        ];
    }

    /**
     * @dataProvider getVirtualRelationQueryProvider
     */
    public function testGetVirtualRelationQuery($class, $field, $isTaggable, $expected)
    {
        $this->setHelperExpectation($isTaggable);
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
                'true',
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
            ['Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable', 'another_relation', true, []],
            ['stdClass', 'tags_virtual', false, []]
        ];
    }

    /**
     * @dataProvider getVirtualRelationsProvider
     */
    public function testGetVirtualRelations($class, $isTaggable, $entityIdentifierFieldName, $expected)
    {
        $this->setHelperExpectation($isTaggable, $entityIdentifierFieldName);
        $this->assertEquals($expected, $this->provider->getVirtualRelations($class));
    }

    public function getVirtualRelationsProvider()
    {
        return [
            [
                'Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable',
                true,
                'pkField',
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
                                            . "virtualTagging.recordId = entity.pkField)"
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
            ['stdClass', false, 'pkField', []]
        ];
    }

    public function testGetTargetJoinAlias()
    {
        $this->assertEquals('virtualTag', $this->provider->getTargetJoinAlias('', ''));
    }

    protected function setHelperExpectation($isTaggable, $entityIdentifierFieldName = 'id')
    {
        $this->taggableHelper->expects($this->any())
            ->method('isTaggable')
            ->willReturn($isTaggable);
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->willReturn($entityIdentifierFieldName);
    }
}
