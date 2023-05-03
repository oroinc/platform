<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Criteria;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;
use Oro\Bundle\TestFrameworkBundle\Test\Stub\ClassWithToString;

class OperationFindCriteriaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider criteriaData
     */
    public function testCriteriaSimpleGetters(?string $entityClass, ?string $route, ?string $datagrid)
    {
        $criteria = new OperationFindCriteria($entityClass, $route, $datagrid);

        $this->assertEquals($entityClass, $criteria->getEntityClass());
        $this->assertEquals($route, $criteria->getRoute());
        $this->assertEquals($datagrid, $criteria->getDatagrid());
    }

    public function criteriaData(): array
    {
        return [
            'simple defaults' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => null
            ],
            'simple values' => [
                'entityClass' => \stdClass::class,
                'route' => 'route1',
                'datagrid' => 'datagrid1'
            ]
        ];
    }

    /**
     * @dataProvider criteriaGroupGetterNormalizationData
     */
    public function testCriteriaGroupGetterNormalization(mixed $set, array $expected)
    {
        $criteria = new OperationFindCriteria(null, null, null, $set);

        $this->assertEquals($expected, $criteria->getGroups());
    }

    public function criteriaGroupGetterNormalizationData(): array
    {
        return [
            'default' => [
                'set' => null,
                'expected' => [ButtonInterface::DEFAULT_GROUP]
            ],
            'convert to array' => [
                'set' => 'group',
                'expected' => ['group']
            ],
            'custom group normalizations' => [
                'set' => [0, 'string'],
                'expected' => ['0', 'string']
            ],
            'correct group object normalization' => [
                'set' => new ClassWithToString(),
                'expected' => ['string representation']
            ]
        ];
    }
}
