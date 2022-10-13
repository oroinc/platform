<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProvider;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrganizationRestrictionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var OrganizationRestrictionProvider */
    private $organizationRestrictionProvider;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->organizationRestrictionProvider = new OrganizationRestrictionProvider($this->tokenAccessor);
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    public function testApplyOrganizationRestrictions(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($this->getOrganization(1));

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('getRootAliases')
            ->willReturn(['e']);
        $qb->expects(self::once())
            ->method('andWhere')
            ->with('e.organization = :organization')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('organization', 1)
            ->willReturnSelf();

        $this->organizationRestrictionProvider->applyOrganizationRestrictions($qb);
    }

    public function testApplyOrganizationRestrictionsWhenNoCurrentOrganization(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(null);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::never())
            ->method(self::anything());

        $this->organizationRestrictionProvider->applyOrganizationRestrictions($qb);
    }

    public function testApplyOrganizationRestrictionsWithProvidedOrganization(): void
    {
        $this->tokenAccessor->expects(self::never())
            ->method('getOrganization');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::never())
            ->method('getRootAliases');
        $qb->expects(self::once())
            ->method('andWhere')
            ->with('a.organization = :organization')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('organization', 1)
            ->willReturnSelf();

        $this->organizationRestrictionProvider->applyOrganizationRestrictions($qb, $this->getOrganization(1), 'a');
    }

    public function testApplyOrganizationRestrictionsToAccessRuleCriteria(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($this->getOrganization(1));

        $criteria = $this->createMock(Criteria::class);
        $criteria->expects(self::once())
            ->method('andExpression')
            ->with(new Comparison(new Path('organization', $criteria->getAlias()), Comparison::EQ, 1));

        $this->organizationRestrictionProvider->applyOrganizationRestrictionsToAccessRuleCriteria($criteria);
    }

    public function testApplyOrganizationRestrictionsToAccessRuleCriteriaWhenNoCurrentOrganization(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(null);

        $criteria = $this->createMock(Criteria::class);
        $criteria->expects(self::never())
            ->method('andExpression');

        $this->organizationRestrictionProvider->applyOrganizationRestrictionsToAccessRuleCriteria($criteria);
    }

    public function testApplyOrganizationRestrictionsToAccessRuleCriteriaWithProvidedOrganization(): void
    {
        $this->tokenAccessor->expects(self::never())
            ->method('getOrganization');

        $criteria = $this->createMock(Criteria::class);
        $criteria->expects(self::once())
            ->method('andExpression')
            ->with(new Comparison(new Path('org', $criteria->getAlias()), Comparison::EQ, 1));

        $this->organizationRestrictionProvider->applyOrganizationRestrictionsToAccessRuleCriteria(
            $criteria,
            $this->getOrganization(1),
            'org'
        );
    }

    public function testGetEnabledOrganizationIds(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($this->getOrganization(1));

        self::assertEquals(
            [1],
            $this->organizationRestrictionProvider->getEnabledOrganizationIds()
        );
    }

    public function testGetEnabledOrganizationIdsWhenNoCurrentOrganization(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(null);

        self::assertSame(
            [],
            $this->organizationRestrictionProvider->getEnabledOrganizationIds()
        );
    }

    public function testGetEnabledOrganizationIdsWithProvidedOrganization(): void
    {
        $this->tokenAccessor->expects(self::never())
            ->method('getOrganization');

        self::assertEquals(
            [1],
            $this->organizationRestrictionProvider->getEnabledOrganizationIds($this->getOrganization(1))
        );
    }

    public function testIsEnabledOrganizationWhenCheckedOrganizationEqualsToCurrentOrganization(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($this->getOrganization(1));

        self::assertTrue($this->organizationRestrictionProvider->isEnabledOrganization($this->getOrganization(1)));
    }

    public function testIsEnabledOrganizationWhenCheckedOrganizationDoesNotEqualToCurrentOrganization(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($this->getOrganization(2));

        self::assertFalse($this->organizationRestrictionProvider->isEnabledOrganization($this->getOrganization(1)));
    }

    public function testIsEnabledOrganizationWhenNoCurrentOrganization(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(null);

        self::assertFalse($this->organizationRestrictionProvider->isEnabledOrganization($this->getOrganization(1)));
    }

    public function testIsEnabledOrganizationWhenCheckedOrganizationEqualsToProvidedOrganization(): void
    {
        $this->tokenAccessor->expects(self::never())
            ->method('getOrganization');

        self::assertTrue(
            $this->organizationRestrictionProvider->isEnabledOrganization(
                $this->getOrganization(1),
                $this->getOrganization(1)
            )
        );
    }

    public function testIsEnabledOrganizationWhenCheckedOrganizationDoesNotEqualToProvidedOrganization(): void
    {
        $this->tokenAccessor->expects(self::never())
            ->method('getOrganization');

        self::assertFalse(
            $this->organizationRestrictionProvider->isEnabledOrganization(
                $this->getOrganization(1),
                $this->getOrganization(2)
            )
        );
    }
}
