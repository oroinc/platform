<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource\Orm;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * @SuppressWarnings(PHPMD)
 */
class OrmQueryConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridConfiguration */
    protected $config;

    /** @var OrmQueryConfiguration */
    protected $query;

    protected function setUp()
    {
        $this->config = DatagridConfiguration::create([]);
        $this->query = new OrmQueryConfiguration($this->config);
    }

    public function testInitialDistinct()
    {
        self::assertFalse($this->query->getDistinct());
    }

    public function testSetDistinct()
    {
        self::assertSame($this->query, $this->query->setDistinct());
        self::assertTrue($this->query->getDistinct());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'distinct' => true
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->setDistinct(false));
        self::assertFalse($this->query->getDistinct());
        self::assertSame(
            [
                'source' => [
                    'query' => []
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testInitialSelect()
    {
        self::assertSame([], $this->query->getSelect());
    }

    public function testSetSelect()
    {
        self::assertSame($this->query, $this->query->setSelect(['column1', 'column2']));
        self::assertSame(['column1', 'column2'], $this->query->getSelect());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'select' => ['column1', 'column2']
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testResetSelect()
    {
        $this->query->setSelect(['column1', 'column2']);
        self::assertSame($this->query, $this->query->resetSelect());
        self::assertSame([], $this->query->getSelect());
        self::assertSame(
            [
                'source' => [
                    'query' => []
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddSelect()
    {
        self::assertSame($this->query, $this->query->addSelect('column1'));
        self::assertSame(['column1'], $this->query->getSelect());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'select' => ['column1']
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addSelect('column2'));
        self::assertSame(['column1', 'column2'], $this->query->getSelect());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'select' => ['column1', 'column2']
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddSelectForMultipleItems()
    {
        self::assertSame($this->query, $this->query->addSelect(['column1']));
        self::assertSame(['column1'], $this->query->getSelect());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'select' => ['column1']
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addSelect(['column2']));
        self::assertSame(['column1', 'column2'], $this->query->getSelect());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'select' => ['column1', 'column2']
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testGetRootAliasWhenNoFromPart()
    {
        self::assertNull($this->query->getRootAlias());
    }

    public function testGetRootAliasForOnlyOneFrom()
    {
        $this->config->offsetSetByPath(
            '[source][query][from]',
            [
                ['table' => 'Test\Entity', 'alias' => 'testAlias']
            ]
        );
        self::assertEquals('testAlias', $this->query->getRootAlias());
    }

    public function testGetRootAliasWhenNoAliasInFrom()
    {
        $this->config->offsetSetByPath(
            '[source][query][from]',
            [
                ['table' => 'Test\Entity']
            ]
        );
        self::assertNull($this->query->getRootAlias());
    }

    public function testGetRootAliasForSeveralFrom()
    {
        $this->config->offsetSetByPath(
            '[source][query][from]',
            [
                ['table' => 'Test\Entity1', 'alias' => 'testAlias1'],
                ['table' => 'Test\Entity2', 'alias' => 'testAlias2']
            ]
        );
        self::assertEquals('testAlias1', $this->query->getRootAlias());
    }

    public function testGetRootEntityWhenNoFromPart()
    {
        self::assertNull($this->query->getRootEntity());
    }

    public function testGetRootEntityWhenLookAtExtendedEntityClassNameIsRequested()
    {
        self::assertNull($this->query->getRootEntity(null, true));

        $this->config->offsetSet('extended_entity_name', 'Test\Entity1');
        self::assertEquals('Test\Entity1', $this->query->getRootEntity(null, true));

        $this->config->offsetSetByPath(
            '[source][query][from]',
            [
                ['table' => 'Test\Entity2', 'alias' => 'testAlias2']
            ]
        );
        self::assertEquals('Test\Entity1', $this->query->getRootEntity(null, true));
    }

    public function testGetRootEntityWithEntityClassResolver()
    {
        $entityClassResolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver->expects(self::once())
            ->method('getEntityClass')
            ->with('Test:Entity')
            ->willReturn('Test\Entity');

        $this->config->offsetSetByPath(
            '[source][query][from]',
            [
                ['table' => 'Test:Entity', 'alias' => 'testAlias']
            ]
        );
        self::assertEquals('Test\Entity', $this->query->getRootEntity($entityClassResolver));
    }

    public function testGetRootEntityForOnlyOneFrom()
    {
        $this->config->offsetSetByPath(
            '[source][query][from]',
            [
                ['table' => 'Test\Entity', 'alias' => 'testAlias']
            ]
        );
        self::assertEquals('Test\Entity', $this->query->getRootEntity());
    }

    public function testGetRootEntityWhenNoEntityInFrom()
    {
        $this->config->offsetSetByPath(
            '[source][query][from]',
            [
                ['alias' => 'testAlias']
            ]
        );
        self::assertNull($this->query->getRootEntity());
    }

    public function testGetRootEntityForSeveralFrom()
    {
        $this->config->offsetSetByPath(
            '[source][query][from]',
            [
                ['table' => 'Test\Entity1', 'alias' => 'testAlias1'],
                ['table' => 'Test\Entity2', 'alias' => 'testAlias2']
            ]
        );
        self::assertEquals('Test\Entity1', $this->query->getRootEntity());
    }

    public function testFindRootEntityWhenNoFromPart()
    {
        self::assertNull($this->query->findRootAlias('Test\Entity'));
    }

    public function testFindRootEntityWhenGivenEntityDoesNotExist()
    {
        $this->config->offsetSetByPath(
            '[source][query][from]',
            [
                ['table' => 'Test\Entity1', 'alias' => 'testAlias1'],
                ['table' => 'Test\Entity2', 'alias' => 'testAlias2']
            ]
        );
        self::assertNull($this->query->findRootAlias('Test\Entity3'));
    }

    public function testFindRootEntity()
    {
        $this->config->offsetSetByPath(
            '[source][query][from]',
            [
                ['table' => 'Test\Entity1', 'alias' => 'testAlias1'],
                ['table' => 'Test\Entity2', 'alias' => 'testAlias2']
            ]
        );
        self::assertEquals('testAlias2', $this->query->findRootAlias('Test\Entity2'));
    }

    public function testFindRootEntityWithEntityClassResolver()
    {
        $entityClassResolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver->expects(self::any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($className) {
                return str_replace(':', '\\', $className);
            });

        $this->config->offsetSetByPath(
            '[source][query][from]',
            [
                ['table' => 'Test:Entity1', 'alias' => 'testAlias1'],
                ['table' => 'Test:Entity2', 'alias' => 'testAlias2']
            ]
        );
        self::assertEquals('testAlias2', $this->query->findRootAlias('Test\Entity2', $entityClassResolver));
    }

    public function testGetJoinAliasForQueryWithoutJoins()
    {
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        self::assertEquals('auto_rel_1', $this->query->getJoinAlias('rootAlias.association1'));
        // ensure that joins was not changed
        self::assertEmpty($this->query->getInnerJoins());
        self::assertEmpty($this->query->getLeftJoins());
        // test that the same alias is returned for the same join
        self::assertEquals('auto_rel_1', $this->query->getJoinAlias('rootAlias.association1'));
    }

    public function testGetJoinAliasForJoinWithConditionForQueryWithoutJoins()
    {
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        self::assertEquals('auto_rel_1', $this->query->getJoinAlias('rootAlias.association1'));
        self::assertEquals(
            'auto_rel_2',
            $this->query->getJoinAlias('rootAlias.association1', 'WITH', 'joinAlias2.id = 123')
        );
        // ensure that joins was not changed
        self::assertEmpty($this->query->getInnerJoins());
        self::assertEmpty($this->query->getLeftJoins());
        // test that the same alias is returned for the same join
        self::assertEquals(
            'auto_rel_2',
            $this->query->getJoinAlias('rootAlias.association1', 'WITH', 'joinAlias2.id = 123')
        );
    }

    public function testGetJoinAliasForExistingJoin()
    {
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin('rootAlias.association1', 'joinAlias');
        self::assertEquals('joinAlias', $this->query->getJoinAlias('rootAlias.association1'));
    }

    public function testGetJoinAliasForExistingJoinWithCondition()
    {
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin('rootAlias.association1', 'joinAlias1');
        $this->query->addLeftJoin('rootAlias.association1', 'joinAlias2', 'WITH', 'joinAlias2.id = 123');
        self::assertEquals('joinAlias1', $this->query->getJoinAlias('rootAlias.association1'));
        self::assertEquals(
            'joinAlias2',
            $this->query->getJoinAlias('rootAlias.association1', 'WITH', 'joinAlias2.id = 123')
        );
    }

    public function testGetJoinAliasWhenExistingJoinHasDifferentCondition()
    {
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin('rootAlias.association1', 'joinAlias1');
        $this->query->addLeftJoin('rootAlias.association1', 'joinAlias2', 'WITH', 'joinAlias2.id = 123');
        self::assertEquals('joinAlias1', $this->query->getJoinAlias('rootAlias.association1'));
        self::assertEquals(
            'auto_rel_1',
            $this->query->getJoinAlias('rootAlias.association1', 'WITH', 'joinAlias2.id = 456')
        );
    }

    public function testConvertAssociationJoinToSubqueryForInnerJoin()
    {
        $this->query->addSelect('joinAlias1.name as testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addInnerJoin('rootAlias.association', 'joinAlias');

        $this->query->convertAssociationJoinToSubquery(
            'joinAlias',
            'testColumn',
            'Test\JoinedEntity'
        );

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            '(SELECT joinAlias1.name FROM Test\JoinedEntity AS joinAlias'
                            . ' WHERE joinAlias = rootAlias.association) AS testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => ['inner' => []]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testConvertAssociationJoinToSubqueryForLeftJoin()
    {
        $this->query->addSelect('joinAlias1.name as testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin('rootAlias.association', 'joinAlias');

        $this->query->convertAssociationJoinToSubquery(
            'joinAlias',
            'testColumn',
            'Test\JoinedEntity'
        );

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            '(SELECT joinAlias1.name FROM Test\JoinedEntity AS joinAlias'
                            . ' WHERE joinAlias = rootAlias.association) AS testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => ['left' => []]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testConvertAssociationJoinToSubqueryWhenAsKeywordIsNotLowercase()
    {
        $this->query->addSelect('joinAlias1.name As testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin('rootAlias.association', 'joinAlias');

        $this->query->convertAssociationJoinToSubquery(
            'joinAlias',
            'testColumn',
            'Test\JoinedEntity'
        );

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            '(SELECT joinAlias1.name FROM Test\JoinedEntity AS joinAlias'
                            . ' WHERE joinAlias = rootAlias.association) AS testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => ['left' => []]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testConvertAssociationJoinToSubqueryForInvalidJoinAlias()
    {
        $this->query->addSelect('joinAlias1.name as testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin('rootAlias.association', 'joinAlias');

        $this->query->convertAssociationJoinToSubquery(
            'invalidJoinAlias',
            'testColumn',
            'Test\JoinedEntity'
        );

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            'joinAlias1.name as testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => [
                            'left' => [
                                ['join' => 'rootAlias.association', 'alias' => 'joinAlias']
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testConvertAssociationJoinToSubqueryForInvalidColumnAlias()
    {
        $this->query->addSelect('joinAlias1.name as testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin('rootAlias.association', 'joinAlias');

        $this->query->convertAssociationJoinToSubquery(
            'joinAlias',
            'invalidColumn',
            'Test\JoinedEntity'
        );

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            'joinAlias1.name as testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => [
                            'left' => [
                                ['join' => 'rootAlias.association', 'alias' => 'joinAlias']
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testConvertAssociationJoinToSubqueryForJoinWithConditions()
    {
        $this->query->addSelect('joinAlias1.name as testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin(
            'rootAlias.association',
            'joinAlias',
            'WITH',
            'joinAlias.primary = true'
        );

        $this->query->convertAssociationJoinToSubquery(
            'joinAlias',
            'testColumn',
            'Test\JoinedEntity'
        );

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            '(SELECT joinAlias1.name FROM Test\JoinedEntity AS joinAlias'
                            . ' WHERE joinAlias = rootAlias.association AND joinAlias.primary = true) AS testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => ['left' => []]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testConvertEntityJoinToSubqueryForInnerJoin()
    {
        $this->query->addSelect('joinAlias1.name as testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addInnerJoin(
            'Test\JoinedEntity',
            'joinAlias',
            'WITH',
            'joinAlias.id = rootAlias'
        );

        $this->query->convertEntityJoinToSubquery('joinAlias', 'testColumn');

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            '(SELECT joinAlias1.name FROM Test\JoinedEntity AS joinAlias'
                            . ' WHERE joinAlias.id = rootAlias) AS testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => ['inner' => []]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testConvertEntityJoinToSubqueryForLeftJoin()
    {
        $this->query->addSelect('joinAlias1.name as testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin(
            'Test\JoinedEntity',
            'joinAlias',
            'WITH',
            'joinAlias.id = rootAlias'
        );

        $this->query->convertEntityJoinToSubquery('joinAlias', 'testColumn');

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            '(SELECT joinAlias1.name FROM Test\JoinedEntity AS joinAlias'
                            . ' WHERE joinAlias.id = rootAlias) AS testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => ['left' => []]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testConvertEntityJoinToSubqueryWhenAsKeywordIsNotLovercase()
    {
        $this->query->addSelect('joinAlias1.name As testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin(
            'Test\JoinedEntity',
            'joinAlias',
            'WITH',
            'joinAlias.id = rootAlias'
        );

        $this->query->convertEntityJoinToSubquery('joinAlias', 'testColumn');

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            '(SELECT joinAlias1.name FROM Test\JoinedEntity AS joinAlias'
                            . ' WHERE joinAlias.id = rootAlias) AS testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => ['left' => []]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testConvertEntityJoinToSubqueryWhenJoinDoesNotHaveCondition()
    {
        $this->query->addSelect('joinAlias1.name as testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin(
            'Test\JoinedEntity',
            'joinAlias'
        );

        $this->query->convertEntityJoinToSubquery('joinAlias', 'testColumn');

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            'joinAlias1.name as testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => [
                            'left' => [
                                ['join' => 'Test\JoinedEntity', 'alias' => 'joinAlias']
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testConvertEntityJoinToSubqueryForInvalidJoinAlias()
    {
        $this->query->addSelect('joinAlias1.name as testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin(
            'Test\JoinedEntity',
            'joinAlias',
            'WITH',
            'joinAlias.id = rootAlias'
        );

        $this->query->convertEntityJoinToSubquery('invalidJoinAlias', 'testColumn');

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            'joinAlias1.name as testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'          => 'Test\JoinedEntity',
                                    'alias'         => 'joinAlias',
                                    'conditionType' => 'WITH',
                                    'condition'     => 'joinAlias.id = rootAlias'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testConvertEntityJoinToSubqueryForInvalidColumnAlias()
    {
        $this->query->addSelect('joinAlias1.name as testColumn');
        $this->query->addFrom('Test\RootEntity', 'rootAlias');
        $this->query->addLeftJoin(
            'Test\JoinedEntity',
            'joinAlias',
            'WITH',
            'joinAlias.id = rootAlias'
        );

        $this->query->convertEntityJoinToSubquery('joinAlias', 'invalidColumn');

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            'joinAlias1.name as testColumn'
                        ],
                        'from'   => [
                            ['table' => 'Test\RootEntity', 'alias' => 'rootAlias']
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'          => 'Test\JoinedEntity',
                                    'alias'         => 'joinAlias',
                                    'conditionType' => 'WITH',
                                    'condition'     => 'joinAlias.id = rootAlias'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testInitialFrom()
    {
        self::assertSame([], $this->query->getFrom());
    }

    public function testSetFrom()
    {
        self::assertSame(
            $this->query,
            $this->query->setFrom([
                ['table' => 'Test\Entity1', 'alias' => 'testAlias1'],
                ['table' => 'Test\Entity2', 'alias' => 'testAlias2']
            ])
        );
        self::assertSame(
            [
                ['table' => 'Test\Entity1', 'alias' => 'testAlias1'],
                ['table' => 'Test\Entity2', 'alias' => 'testAlias2']
            ],
            $this->query->getFrom()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'from' => [
                            ['table' => 'Test\Entity1', 'alias' => 'testAlias1'],
                            ['table' => 'Test\Entity2', 'alias' => 'testAlias2']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testResetFrom()
    {
        $this->query->setFrom([
            ['table' => 'Test\Entity1', 'alias' => 'testAlias1'],
            ['table' => 'Test\Entity2', 'alias' => 'testAlias2']
        ]);
        self::assertSame($this->query, $this->query->resetFrom());
        self::assertSame([], $this->query->getFrom());
        self::assertSame(
            [
                'source' => [
                    'query' => []
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddFrom()
    {
        self::assertSame($this->query, $this->query->addFrom('Test\Entity1', 'testAlias1'));
        self::assertSame(
            [
                ['table' => 'Test\Entity1', 'alias' => 'testAlias1']
            ],
            $this->query->getFrom()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'from' => [
                            ['table' => 'Test\Entity1', 'alias' => 'testAlias1']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addFrom('Test\Entity2', 'testAlias2'));
        self::assertSame(
            [
                ['table' => 'Test\Entity1', 'alias' => 'testAlias1'],
                ['table' => 'Test\Entity2', 'alias' => 'testAlias2']
            ],
            $this->query->getFrom()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'from' => [
                            ['table' => 'Test\Entity1', 'alias' => 'testAlias1'],
                            ['table' => 'Test\Entity2', 'alias' => 'testAlias2']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testInitialInnerJoins()
    {
        self::assertSame([], $this->query->getInnerJoins());
    }

    public function testSetInnerJoins()
    {
        self::assertSame(
            $this->query,
            $this->query->setInnerJoins([
                ['join' => 'alias.association1', 'alias' => 'testAlias1'],
                ['join' => 'alias.association2', 'alias' => 'testAlias2']
            ])
        );
        self::assertSame(
            [
                ['join' => 'alias.association1', 'alias' => 'testAlias1'],
                ['join' => 'alias.association2', 'alias' => 'testAlias2']
            ],
            $this->query->getInnerJoins()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'join' => [
                            'inner' => [
                                ['join' => 'alias.association1', 'alias' => 'testAlias1'],
                                ['join' => 'alias.association2', 'alias' => 'testAlias2']
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddInnerJoin()
    {
        self::assertSame($this->query, $this->query->addInnerJoin('alias.association1', 'testAlias1'));
        self::assertSame(
            [
                ['join' => 'alias.association1', 'alias' => 'testAlias1']
            ],
            $this->query->getInnerJoins()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'join' => [
                            'inner' => [
                                ['join' => 'alias.association1', 'alias' => 'testAlias1']
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addInnerJoin('alias.association2', 'testAlias2'));
        self::assertSame(
            [
                ['join' => 'alias.association1', 'alias' => 'testAlias1'],
                ['join' => 'alias.association2', 'alias' => 'testAlias2']
            ],
            $this->query->getInnerJoins()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'join' => [
                            'inner' => [
                                ['join' => 'alias.association1', 'alias' => 'testAlias1'],
                                ['join' => 'alias.association2', 'alias' => 'testAlias2']
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddInnerJoinWithCondition()
    {
        self::assertSame(
            $this->query,
            $this->query->addInnerJoin('alias.association1', 'testAlias1', 'WITH', 'testAlias1.id = 123')
        );
        self::assertSame(
            [
                [
                    'join'          => 'alias.association1',
                    'alias'         => 'testAlias1',
                    'conditionType' => 'WITH',
                    'condition'     => 'testAlias1.id = 123'
                ]
            ],
            $this->query->getInnerJoins()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'join' => [
                            'inner' => [
                                [
                                    'join'          => 'alias.association1',
                                    'alias'         => 'testAlias1',
                                    'conditionType' => 'WITH',
                                    'condition'     => 'testAlias1.id = 123'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testInitialLeftJoins()
    {
        self::assertSame([], $this->query->getLeftJoins());
    }

    public function testSetLeftJoins()
    {
        self::assertSame(
            $this->query,
            $this->query->setLeftJoins([
                ['join' => 'alias.association1', 'alias' => 'testAlias1'],
                ['join' => 'alias.association2', 'alias' => 'testAlias2']
            ])
        );
        self::assertSame(
            [
                ['join' => 'alias.association1', 'alias' => 'testAlias1'],
                ['join' => 'alias.association2', 'alias' => 'testAlias2']
            ],
            $this->query->getLeftJoins()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'join' => [
                            'left' => [
                                ['join' => 'alias.association1', 'alias' => 'testAlias1'],
                                ['join' => 'alias.association2', 'alias' => 'testAlias2']
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddLeftJoin()
    {
        self::assertSame($this->query, $this->query->addLeftJoin('alias.association1', 'testAlias1'));
        self::assertSame(
            [
                ['join' => 'alias.association1', 'alias' => 'testAlias1']
            ],
            $this->query->getLeftJoins()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'join' => [
                            'left' => [
                                ['join' => 'alias.association1', 'alias' => 'testAlias1']
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addLeftJoin('alias.association2', 'testAlias2'));
        self::assertSame(
            [
                ['join' => 'alias.association1', 'alias' => 'testAlias1'],
                ['join' => 'alias.association2', 'alias' => 'testAlias2']
            ],
            $this->query->getLeftJoins()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'join' => [
                            'left' => [
                                ['join' => 'alias.association1', 'alias' => 'testAlias1'],
                                ['join' => 'alias.association2', 'alias' => 'testAlias2']
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddLeftJoinWithCondition()
    {
        self::assertSame(
            $this->query,
            $this->query->addLeftJoin('alias.association1', 'testAlias1', 'WITH', 'testAlias1.id = 123')
        );
        self::assertSame(
            [
                [
                    'join'          => 'alias.association1',
                    'alias'         => 'testAlias1',
                    'conditionType' => 'WITH',
                    'condition'     => 'testAlias1.id = 123'
                ]
            ],
            $this->query->getLeftJoins()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'join' => [
                            'left' => [
                                [
                                    'join'          => 'alias.association1',
                                    'alias'         => 'testAlias1',
                                    'conditionType' => 'WITH',
                                    'condition'     => 'testAlias1.id = 123'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testInitialWhere()
    {
        self::assertSame([], $this->query->getWhere());
    }

    public function testSetWhere()
    {
        self::assertSame(
            $this->query,
            $this->query->setWhere(['and' => ['column1 = 123']])
        );
        self::assertSame(['and' => ['column1 = 123']], $this->query->getWhere());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'where' => [
                            'and' => ['column1 = 123']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testResetWhere()
    {
        $this->query->setWhere(['and' => ['column1 = 123']]);
        self::assertSame($this->query, $this->query->resetWhere());
        self::assertSame([], $this->query->getWhere());
        self::assertSame(
            [
                'source' => [
                    'query' => []
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddAndWhere()
    {
        self::assertSame($this->query, $this->query->addAndWhere('column1 = 123'));
        self::assertSame(['and' => ['column1 = 123']], $this->query->getWhere());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'where' => [
                            'and' => ['column1 = 123']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addAndWhere('column2 = 456'));
        self::assertSame(['and' => ['column1 = 123', 'column2 = 456']], $this->query->getWhere());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'where' => [
                            'and' => ['column1 = 123', 'column2 = 456']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddAndWhereForMultipleItems()
    {
        self::assertSame($this->query, $this->query->addAndWhere(['column1 = 123']));
        self::assertSame(['and' => ['column1 = 123']], $this->query->getWhere());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'where' => [
                            'and' => ['column1 = 123']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addAndWhere(['column2 = 456']));
        self::assertSame(['and' => ['column1 = 123', 'column2 = 456']], $this->query->getWhere());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'where' => [
                            'and' => ['column1 = 123', 'column2 = 456']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddOrWhere()
    {
        self::assertSame($this->query, $this->query->addOrWhere('column1 = 123'));
        self::assertSame(['or' => ['column1 = 123']], $this->query->getWhere());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'where' => [
                            'or' => ['column1 = 123']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addOrWhere('column2 = 456'));
        self::assertSame(['or' => ['column1 = 123', 'column2 = 456']], $this->query->getWhere());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'where' => [
                            'or' => ['column1 = 123', 'column2 = 456']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddOrWhereForMultipleItems()
    {
        self::assertSame($this->query, $this->query->addOrWhere(['column1 = 123']));
        self::assertSame(['or' => ['column1 = 123']], $this->query->getWhere());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'where' => [
                            'or' => ['column1 = 123']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addOrWhere(['column2 = 456']));
        self::assertSame(['or' => ['column1 = 123', 'column2 = 456']], $this->query->getWhere());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'where' => [
                            'or' => ['column1 = 123', 'column2 = 456']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testInitialHaving()
    {
        self::assertNull($this->query->getHaving());
    }

    public function testSetHaving()
    {
        self::assertSame($this->query, $this->query->setHaving('column1,column2'));
        self::assertSame('column1,column2', $this->query->getHaving());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'having' => 'column1,column2'
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testResetHaving()
    {
        $this->query->setHaving('column1,column2');
        self::assertSame($this->query, $this->query->resetHaving());
        self::assertNull($this->query->getHaving());
        self::assertSame(
            [
                'source' => [
                    'query' => []
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddHaving()
    {
        self::assertSame($this->query, $this->query->addHaving('column1'));
        self::assertSame('column1', $this->query->getHaving());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'having' => 'column1'
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addHaving('column2'));
        self::assertSame('column1,column2', $this->query->getHaving());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'having' => 'column1,column2'
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testInitialGroupBy()
    {
        self::assertNull($this->query->getGroupBy());
    }

    public function testSetGroupBy()
    {
        self::assertSame($this->query, $this->query->setGroupBy('column1,column2'));
        self::assertSame('column1,column2', $this->query->getGroupBy());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'groupBy' => 'column1,column2'
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testResetGroupBy()
    {
        $this->query->setGroupBy('column1,column2');
        self::assertSame($this->query, $this->query->resetGroupBy());
        self::assertNull($this->query->getGroupBy());
        self::assertSame(
            [
                'source' => [
                    'query' => []
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddGroupBy()
    {
        self::assertSame($this->query, $this->query->addGroupBy('column1'));
        self::assertSame('column1', $this->query->getGroupBy());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'groupBy' => 'column1'
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addGroupBy('column2'));
        self::assertSame('column1,column2', $this->query->getGroupBy());
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'groupBy' => 'column1,column2'
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testInitialOrderBy()
    {
        self::assertSame([], $this->query->getOrderBy());
    }

    public function testSetOrderBy()
    {
        self::assertSame(
            $this->query,
            $this->query->setOrderBy([
                ['column' => 'column1', 'dir' => 'asc'],
                ['column' => 'column2', 'dir' => 'desc']
            ])
        );
        self::assertSame(
            [
                ['column' => 'column1', 'dir' => 'asc'],
                ['column' => 'column2', 'dir' => 'desc']
            ],
            $this->query->getOrderBy()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'orderBy' => [
                            ['column' => 'column1', 'dir' => 'asc'],
                            ['column' => 'column2', 'dir' => 'desc']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testResetOrderBy()
    {
        $this->query->setOrderBy([
            ['column' => 'column1', 'dir' => 'asc'],
            ['column' => 'column2', 'dir' => 'desc']
        ]);
        self::assertSame($this->query, $this->query->resetOrderBy());
        self::assertSame([], $this->query->getOrderBy());
        self::assertSame(
            [
                'source' => [
                    'query' => []
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testAddOrderBy()
    {
        self::assertSame($this->query, $this->query->addOrderBy('column1'));
        self::assertSame(
            [
                ['column' => 'column1', 'dir' => 'asc']
            ],
            $this->query->getOrderBy()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'orderBy' => [
                            ['column' => 'column1', 'dir' => 'asc']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addOrderBy('column2', 'desc'));
        self::assertSame(
            [
                ['column' => 'column1', 'dir' => 'asc'],
                ['column' => 'column2', 'dir' => 'desc']
            ],
            $this->query->getOrderBy()
        );
        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'orderBy' => [
                            ['column' => 'column1', 'dir' => 'asc'],
                            ['column' => 'column2', 'dir' => 'desc']
                        ]
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testInitialHints()
    {
        self::assertSame([], $this->query->getHints());
    }

    public function testSetHints()
    {
        self::assertSame(
            $this->query,
            $this->query->setHints([
                'hint1',
                ['name' => 'hint2', 'value' => 'hintVal2']
            ])
        );
        self::assertSame(
            [
                'hint1',
                ['name' => 'hint2', 'value' => 'hintVal2']
            ],
            $this->query->getHints()
        );
        self::assertSame(
            [
                'source' => [
                    'hints' => [
                        'hint1',
                        ['name' => 'hint2', 'value' => 'hintVal2']
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    public function testResetHints()
    {
        $this->query->setHints([
            'hint1',
            ['name' => 'hint2', 'value' => 'hintVal2']
        ]);
        self::assertSame($this->query, $this->query->resetHints());
        self::assertSame([], $this->query->getHints());
        self::assertSame(
            [
                'source' => []
            ],
            $this->config->toArray()
        );
    }

    public function testAddHint()
    {
        self::assertSame($this->query, $this->query->addHint('hint1'));
        self::assertSame(
            [
                'hint1'
            ],
            $this->query->getHints()
        );
        self::assertSame(
            [
                'source' => [
                    'hints' => [
                        'hint1'
                    ]
                ]
            ],
            $this->config->toArray()
        );

        self::assertSame($this->query, $this->query->addHint('hint2', 'hintVal2'));
        self::assertSame(
            [
                'hint1',
                ['name' => 'hint2', 'value' => 'hintVal2']
            ],
            $this->query->getHints()
        );
        self::assertSame(
            [
                'source' => [
                    'hints' => [
                        'hint1',
                        ['name' => 'hint2', 'value' => 'hintVal2']
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }
}
