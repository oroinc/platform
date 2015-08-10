<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Search;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\Search\AclHelper;

class TestAclHelper extends \PHPUnit_Framework_TestCase
{
    /** @var AclHelper */
    protected $aclHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mappingProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $ownershipDataBuilder;

    /**
     *  @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function setUp()
    {
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacade->expects($this->any())
            ->method('getOrganizationId')
            ->willReturn(1);

        $this->mappingProvider = $this->getMockBuilder('Oro\Bundle\SearchBundle\Provider\SearchMappingProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ownershipDataBuilder = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper = new AclHelper($this->mappingProvider, $securityFacade, $this->ownershipDataBuilder);

        $mappings = [
            'Oro\Test\Entity\User'      => [
                'alias'        => 'testUser',
                'aclCondition' => null // no access
            ],
            'Oro\Test\Entity\Product'   => [
                'alias'        => 'testProduct',
                'aclCondition' => [] // full access
            ],
            'Oro\Test\Entity\Price'     => [
                'alias'        => 'testPrice',
                'aclCondition' => [
                    'owner',
                    null // full access
                ]
            ],
            'Oro\Test\Entity\OrderItem' => [
                'alias'        => 'testOrderItem',
                'aclCondition' => [
                    'owner',
                    [] // no access
                ]
            ],
            'Oro\Test\Entity\Category'  => [
                'alias'        => 'testCategory',
                'aclCondition' => [
                    'owner',
                    [3]
                ]
            ],
            'Oro\Test\Entity\Order'     => [
                'alias'        => 'testOrder',
                'aclCondition' => [
                    'owner',
                    [3, 5, 4, 6]
                ]
            ],
        ];

        $this->mappingProvider->expects($this->any())
            ->method('getEntitiesListAliases')
            ->willReturnCallback(
                function () use ($mappings) {
                    $result = [];
                    foreach ($mappings as $className => $mapping) {
                        $result[$className] = $mapping['alias'];
                    }

                    return $result;
                }
            );

        $this->mappingProvider->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(
                function ($alias) use ($mappings) {
                    foreach ($mappings as $className => $mapping) {
                        if ($mapping['alias'] == $alias) {
                            return $className;
                        }
                    }

                    return null;
                }
            );

        $this->ownershipDataBuilder->expects($this->any())
            ->method('getAclConditionData')
            ->willReturnCallback(
                function ($class) use ($mappings) {
                    foreach ($mappings as $className => $mapping) {
                        if ($class === $className) {
                            return $mapping['aclCondition'];
                        }
                    }

                    return null;
                }
            );
    }

    /**
     * @dataProvider applyTestCases
     *
     * @param mixed  $from
     * @param string $expectedQuery
     */
    public function testApply($from, $expectedQuery)
    {
        $query = new Query();
        $query->from($from);
        $this->aclHelper->apply($query);
        $this->assertEquals($expectedQuery, $query->getStringQuery());
    }

    public function applyTestCases()
    {
        return [
            'select from *'             => [
                '*',
                ' from testProduct, testPrice, testOrderItem, testCategory, testOrder where '
                . '((integer testProduct_owner >= 0 or integer testPrice_owner >= 0 or integer '
                . 'testOrderItem_owner in (0) or integer testCategory_owner = 3 or integer testOrder_owner '
                . 'in (3, 5, 4, 6)) and integer organization in (1, 0))'
            ],
            'select with unknown alias' => [
                ['testProduct', 'badAlias'],
                ' from testProduct where ((integer testProduct_owner >= 0) and integer organization in (1, 0))'
            ]
        ];
    }
}
