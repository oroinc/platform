<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\OrderBy;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Sorter\PostgresqlGridModifier;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class PostgresqlGridModifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PostgresqlGridModifier
     */
    protected $extension;

    protected $entityClassResolver;

    public function setUp()
    {
        $this->entityClassResolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new PostgresqlGridModifier('pdo_pgsql', $this->entityClassResolver);
    }

    /**
     * @dataProvider visitDatasourceDataProvider
     */
    public function testVisitDatasource($orderBy, $expected)
    {
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with('Test\Entity')
            ->willReturn($metadata);

        $metadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $qb = new QueryBuilder($em);
        $qb->select('e.id')->from('Test\Entity', 'e');

        if (!empty($orderBy)) {
            $orderByExpr = new OrderBy();
            foreach ($orderBy as $field => $des) {
                $orderByExpr->add($field, $des);
            }
            $qb->addOrderBy($orderByExpr);
        }

        $datasource = $this->getMockBuilder(OrmDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->willReturn('Test\Entity');

        $dataGridConfig = DatagridConfiguration::create([
            'sorters' => [
                'columns' => []
            ],
            'source'  => [
                'type'    => 'orm',
                'query'   => [
                    'from' => [
                        [
                            'table' => 'Test\Entity',
                            'alias' => 'e'
                        ]
                    ]
                ]
            ]
        ]);

        $this->extension->visitDatasource(
            $dataGridConfig,
            $datasource
        );

        self::assertEquals(
            $expected,
            $qb->getDQL()
        );
    }

    /**
     * @return array
     */
    public function visitDatasourceDataProvider()
    {
        return [
            'OrderBy has only primary key field'     => [
                ['e.id' => 'ASC'],
                'SELECT e.id FROM Test\Entity e ORDER BY e.id ASC'
            ],
            'OrderBy has no primary key field'       => [
                ['e.name' => 'ASC'],
                'SELECT e.id FROM Test\Entity e ORDER BY e.name ASC, e.id ASC'
            ],
            'OrderBy has no fields'                  => [
                [],
                'SELECT e.id FROM Test\Entity e ORDER BY e.id ASC'
            ],
            'OrderBy has primary key field'          => [
                ['e.name' => 'DESC', 'e.id' => 'DESC'],
                'SELECT e.id FROM Test\Entity e ORDER BY e.name DESC, e.id DESC'
            ],
            'OrderBy has primary key field as first' => [
                ['e.id' => 'DESC', 'e.name' => 'ASC'],
                'SELECT e.id FROM Test\Entity e ORDER BY e.id DESC, e.name ASC'
            ],
        ];
    }
}
