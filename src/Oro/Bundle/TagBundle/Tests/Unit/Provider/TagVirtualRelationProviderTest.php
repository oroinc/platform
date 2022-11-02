<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Provider\TagVirtualRelationProvider;
use Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable;

class TagVirtualRelationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaggableHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $taggableHelper;

    /** @var TaggableHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var TagVirtualRelationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->taggableHelper = $this->createMock(TaggableHelper::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->provider = new TagVirtualRelationProvider(
            $this->taggableHelper,
            $this->doctrineHelper
        );
    }

    /**
     * @dataProvider isVirtualRelationProvider
     */
    public function testIsVirtualRelation(string $class, string $field, bool $isTaggable, bool $expected)
    {
        $this->setHelperExpectation($isTaggable);
        $this->assertSame($expected, $this->provider->isVirtualRelation($class, $field));
    }

    public function isVirtualRelationProvider(): array
    {
        return [
            [Taggable::class, 'tags_virtual', true, true],
            [Taggable::class, 'another_relation', true, false],
            ['stdClass', 'tags_virtual', false, false]
        ];
    }

    /**
     * @dataProvider getVirtualRelationQueryProvider
     */
    public function testGetVirtualRelationQuery(string $class, string $field, bool $isTaggable, array $expected)
    {
        $this->setHelperExpectation($isTaggable);
        $this->assertEquals($expected, $this->provider->getVirtualRelationQuery($class, $field));
    }

    public function getVirtualRelationQueryProvider(): array
    {
        return [
            [
                Taggable::class,
                'tags_virtual',
                true,
                [
                    'join' => [
                        'left' => [
                            [
                                'join'          => Tagging::class,
                                'alias'         => 'virtualTagging',
                                'conditionType' => 'WITH',
                                'condition'     => '(virtualTagging.entityName = '
                                    . "'Oro\\Bundle\\TagBundle\\Tests\\Unit\\Fixtures\\Taggable' and "
                                    . 'virtualTagging.recordId = entity.id)'
                            ],
                            [
                                'join'  => 'virtualTagging.tag',
                                'alias' => 'virtualTag'
                            ]
                        ]
                    ]
                ]
            ],
            [Taggable::class, 'another_relation', true, []],
            ['stdClass', 'tags_virtual', false, []]
        ];
    }

    /**
     * @dataProvider getVirtualRelationsProvider
     */
    public function testGetVirtualRelations(
        string $class,
        bool $isTaggable,
        string $entityIdentifierFieldName,
        array $expected
    ) {
        $this->setHelperExpectation($isTaggable, $entityIdentifierFieldName);
        $this->assertEquals($expected, $this->provider->getVirtualRelations($class));
    }

    public function getVirtualRelationsProvider(): array
    {
        return [
            [
                Taggable::class,
                true,
                'pkField',
                [
                    'tags_virtual' => [
                        'label' => 'oro.tag.entity_plural_label',
                        'relation_type' => 'ManyToMany',
                        'related_entity_name' => Tag::class,
                        'target_join_alias' => 'virtualTag',
                        'query' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join'          => Tagging::class,
                                        'alias'         => 'virtualTagging',
                                        'conditionType' => 'WITH',
                                        'condition'     => '(virtualTagging.entityName = '
                                            . "'Oro\\Bundle\\TagBundle\\Tests\\Unit\\Fixtures\\Taggable' and "
                                            . 'virtualTagging.recordId = entity.pkField)'
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

    private function setHelperExpectation(bool $isTaggable, string $entityIdentifierFieldName = 'id')
    {
        $this->taggableHelper->expects($this->any())
            ->method('isTaggable')
            ->willReturn($isTaggable);
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->willReturn($entityIdentifierFieldName);
    }
}
