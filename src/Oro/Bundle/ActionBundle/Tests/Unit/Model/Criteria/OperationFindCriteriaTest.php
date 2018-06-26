<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Criteria;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;
use Oro\Bundle\TestFrameworkBundle\Test\Stub\ClassWithToString;

class OperationFindCriteriaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider criteriaData
     *
     * @param null|string $entityClass
     * @param null|string $route
     * @param null|string $datagrid
     */
    public function testCriteriaSimpleGetters($entityClass, $route, $datagrid)
    {
        $criteria = new OperationFindCriteria($entityClass, $route, $datagrid);

        $this->assertEquals($entityClass, $criteria->getEntityClass());
        $this->assertEquals($route, $criteria->getRoute());
        $this->assertEquals($datagrid, $criteria->getDatagrid());
    }

    /**
     * @return \Generator
     */
    public function criteriaData()
    {
        yield 'simple defaults' => [
            'entityClass' => null,
            'route' => null,
            'datagrid' => null
        ];

        yield 'simple values' => [
            'entityClass' => \stdClass::class,
            'route' => 'route1',
            'datagrid' => 'datagrid1'
        ];
    }

    /**
     * @dataProvider criteriaGroupGetterNormalizationData
     *
     * @param null|string|array $setGroup
     * @param array $expected
     */
    public function testCriteriaGroupGetterNormalization($setGroup, array $expected)
    {
        $criteria = new OperationFindCriteria(null, null, null, $setGroup);

        $this->assertEquals($expected, $criteria->getGroups());
    }

    /**
     * @return \Generator
     */
    public function criteriaGroupGetterNormalizationData()
    {
        yield 'default' => [
            'set' => null,
            'expected' => [ButtonInterface::DEFAULT_GROUP]
        ];

        yield 'convert to array' => [
            'set' => 'group',
            'expected' => ['group']
        ];

        yield 'custom group normalizations' => [
            'set' => [0, 'string'],
            'expected' => ['0', 'string']
        ];

        yield 'correct group object normalization' => [
            'set' => new ClassWithToString,
            'expected' => ['string representation']
        ];
    }
}
