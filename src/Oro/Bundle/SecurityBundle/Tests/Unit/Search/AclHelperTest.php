<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Search;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Search\AclHelper;
use Oro\Bundle\SecurityBundle\Search\SearchAclHelperConditionProvider;

class AclHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclHelper */
    private $aclHelper;

    /** @var SearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $mappingProvider;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var OwnershipConditionDataBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $ownershipDataBuilder;

    /** @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ownershipMetadataProvider;

    /** @var OwnershipMetadataInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ownershipMetadata;

    /** @var SearchAclHelperConditionProvider|\PHPUnit\Framework\MockObject\MockObject  */
    private $searchAclHelperConditionProvider;

    /** @var array */
    private $mappings = [
        'Oro\Test\Entity\Organization'      => [
            'alias'        => 'testOrganization',
            'aclCondition' => [null, null, 'organization', 1, false] // no access
        ],
        'Oro\Test\Entity\User'      => [
            'alias'        => 'testUser',
            'aclCondition' => [null, null, null, null] // no access
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
        'Oro\Test\Entity\BusinessUnit'   => [
            'alias'        => 'businessUnit',
            'aclCondition' => [
                'id',
                [5, 6]
            ]
        ],
    ];

    protected function setUp(): void
    {
        $this->mappingProvider = $this->createMock(SearchMappingProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->ownershipDataBuilder = $this->createMock(OwnershipConditionDataBuilder::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->ownershipMetadata = $this->createMock(OwnershipMetadataInterface::class);
        $this->searchAclHelperConditionProvider = $this->createMock(SearchAclHelperConditionProvider::class);

        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getMetadata')
            ->willReturn($this->ownershipMetadata);

        $this->aclHelper = new AclHelper(
            $this->mappingProvider,
            $this->tokenAccessor,
            $this->ownershipDataBuilder,
            $this->ownershipMetadataProvider,
            $this->searchAclHelperConditionProvider
        );
    }

    /**
     * @dataProvider applyTestCases
     */
    public function testApply(mixed $from, string $ownerColumnName, string $expectedQuery)
    {
        $mappings = $this->mappings;

        $this->searchAclHelperConditionProvider->expects(self::any())
            ->method('isApplicable')
            ->willReturn(false);
        $this->searchAclHelperConditionProvider->expects(self::never())
            ->method('addRestriction');

        $this->mappingProvider->expects($this->any())
            ->method('getEntitiesListAliases')
            ->willReturnCallback(function () use ($mappings) {
                $result = [];
                foreach ($mappings as $className => $mapping) {
                    $result[$className] = $mapping['alias'];
                }

                return $result;
            });
        $this->mappingProvider->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($alias) use ($mappings) {
                foreach ($mappings as $className => $mapping) {
                    if ($mapping['alias'] === $alias) {
                        return $className;
                    }
                }

                return null;
            });

        $this->tokenAccessor->expects($this->any())
            ->method('getOrganizationId')
            ->willReturn(1);

        $this->ownershipDataBuilder->expects($this->any())
            ->method('getAclConditionData')
            ->willReturnCallback(function ($class) use ($mappings) {
                foreach ($mappings as $className => $mapping) {
                    if ($class === $className) {
                        return $mapping['aclCondition'];
                    }
                }

                return null;
            });

        $this->ownershipMetadata->expects($this->any())
            ->method('getOwnerFieldName')
            ->willReturn($ownerColumnName);

        $query = new Query();
        $query->from($from);
        $this->aclHelper->apply($query);
        $this->assertEquals($expectedQuery, $query->getStringQuery());
    }

    public function applyTestCases(): array
    {
        return [
            'select from *'             => [
                '*',
                'owner',
                'from testProduct, testPrice, testOrderItem, testCategory, testOrder, businessUnit where '
                . '((integer testProduct_owner >= 0 or integer testPrice_owner >= 0 or integer '
                . 'testOrderItem_owner = 0 or integer testCategory_owner = 3 or integer testOrder_owner '
                . 'in (3, 5, 4, 6) or integer businessUnit_id in (5, 6)) and integer organization in (1, 0))'
            ],
            'select with unknown alias' => [
                ['testProduct', 'badAlias'],
                'owner2',
                'from testProduct where ((integer testProduct_owner2 >= 0) and integer organization in (1, 0))'
            ],
            'select when condition field is not equal to owner field' => [
                ['businessUnit', 'buAlias'],
                'owner',
                'from businessUnit where ((integer businessUnit_id in (5, 6)) and integer organization in (1, 0))'
            ]
        ];
    }

    public function testApplyWithConditionFromProviders(): void
    {
        $mappings = $this->mappings;
        $query = new Query();
        $query->from(['testProduct', 'businessUnit']);
        $query->getCriteria()->andWhere(new Comparison('all_text', Comparison::EQ, new Value('some_value')));


        $this->searchAclHelperConditionProvider->expects(self::exactly(2))
            ->method('isApplicable')
            ->willReturnMap([
                ['Oro\Test\Entity\Product', 'VIEW', false],
                ['Oro\Test\Entity\BusinessUnit', 'VIEW', true]
            ]);

        $this->searchAclHelperConditionProvider->expects(self::once())
            ->method('addRestriction')
            ->willReturnCallback(function ($query, $className, $permission, $alias, $orExpression) {
                $query->from(array_merge($query->getFrom(), [$alias]));
                $expressionBuilder = new ExpressionBuilder();

                return new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [$orExpression, $expressionBuilder->eq('test', 'value')]
                );
            });

        $this->mappingProvider->expects($this->any())
            ->method('getEntitiesListAliases')
            ->willReturnCallback(function () use ($mappings) {
                $result = [];
                foreach ($mappings as $className => $mapping) {
                    $result[$className] = $mapping['alias'];
                }

                return $result;
            });
        $this->mappingProvider->expects($this->exactly(2))
            ->method('getEntityClass')
            ->willReturnCallback(function ($alias) use ($mappings) {
                foreach ($mappings as $className => $mapping) {
                    if ($mapping['alias'] === $alias) {
                        return $className;
                    }
                }

                return null;
            });

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(1);

        $this->ownershipDataBuilder->expects($this->once())
            ->method('getAclConditionData')
            ->willReturn([]);

        $this->ownershipMetadata->expects($this->once())
            ->method('getOwnerFieldName')
            ->willReturn('product_owner');

        $this->aclHelper->apply($query);

        $this->assertEquals(
            'from testProduct, businessUnit where'
                . ' ((text all_text = "some_value"'
                . ' and ((integer testProduct_product_owner >= 0) or text test = "value"))'
                . ' and integer organization in (1, 0))',
            $query->getStringQuery()
        );
    }
}
