<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Symfony\Component\Form\FormFactoryInterface;

class StringFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var StringFilter */
    private $filter;

    protected function setUp(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new StringFilter($formFactory, new FilterUtility());
        $this->filter->init('test-filter', [
            FilterUtility::DATA_NAME_KEY => 'field_name'
        ]);
    }

    private function getFilterDatasource(): OrmFilterDatasourceAdapter
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getExpressionBuilder')
            ->willReturn(new Query\Expr());
        $connection = $this->createMock(Connection::class);
        $em->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        return new OrmFilterDatasourceAdapter(new QueryBuilder($em));
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     *
     * @return string
     */
    private function parseQueryCondition(OrmFilterDatasourceAdapter $ds)
    {
        $qb = $ds->getQueryBuilder();

        $parameters = [];
        foreach ($qb->getParameters() as $param) {
            /* @var Query\Parameter $param */
            $parameters[':' . $param->getName()] = $param->getValue();
        }

        $parts = $qb->getDQLParts();
        if (!$parts['where']) {
            return '';
        }

        return str_replace(
            array_keys($parameters),
            array_values($parameters),
            (string)$parts['where']
        );
    }

    /**
     * @dataProvider applyProvider
     */
    public function testApply(array $data, array $expected)
    {
        $ds = $this->getFilterDatasource();
        $this->filter->apply($ds, $data);

        $where = $this->parseQueryCondition($ds);
        self::assertEquals($expected['where'], $where);
    }

    public function applyProvider(): array
    {
        return [
            'NO_TYPE'               => [
                'data'     => [
                    'value' => 'test'
                ],
                'expected' => [
                    'where' => 'field_name = test'
                ]
            ],
            'EMPTY'                 => [
                'data'     => [
                    'type'  => FilterUtility::TYPE_EMPTY,
                    'value' => null
                ],
                'expected' => [
                    'where' => 'field_name IS NULL OR field_name = \'\''
                ]
            ],
            'NOT_EMPTY'             => [
                'data'     => [
                    'type'  => FilterUtility::TYPE_NOT_EMPTY,
                    'value' => null
                ],
                'expected' => [
                    'where' => 'field_name IS NOT NULL AND field_name <> \'\''
                ]
            ],
            'CONTAINS_EMPTY_STRING' => [
                'data'     => [
                    'type'  => TextFilterType::TYPE_CONTAINS,
                    'value' => ''
                ],
                'expected' => [
                    'where' => ''
                ]
            ],
            'CONTAINS_STRING'       => [
                'data'     => [
                    'type'  => TextFilterType::TYPE_CONTAINS,
                    'value' => 'test'
                ],
                'expected' => [
                    'where' => 'field_name LIKE %test%'
                ]
            ],
            'CONTAINS_STRING_0'     => [
                'data'     => [
                    'type'  => TextFilterType::TYPE_CONTAINS,
                    'value' => '0'
                ],
                'expected' => [
                    'where' => 'field_name LIKE %0%'
                ]
            ]
        ];
    }

    public function testPrepareData()
    {
        $data = [];
        self::assertSame($data, $this->filter->prepareData($data));
    }
}
