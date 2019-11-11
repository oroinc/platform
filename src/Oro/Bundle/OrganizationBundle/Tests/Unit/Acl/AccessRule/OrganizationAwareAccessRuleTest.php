<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\OrganizationBundle\Acl\AccessRule\OrganizationAwareAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\NullComparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class OrganizationAwareAccessRuleTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
    }

    public function testIsApplicableWithoutOrganization(): void
    {
        /** @var Criteria|\PHPUnit\Framework\MockObject\MockObject $criteria */
        $criteria = $this->createMock(Criteria::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(null);

        $accessRule = new OrganizationAwareAccessRule($this->tokenAccessor);
        $this->assertFalse($accessRule->isApplicable($criteria));
    }

    public function testIsApplicableWithOrganization(): void
    {
        /** @var Criteria|\PHPUnit\Framework\MockObject\MockObject $criteria */
        $criteria = $this->createMock(Criteria::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(1);

        $accessRule = new OrganizationAwareAccessRule($this->tokenAccessor);
        $this->assertTrue($accessRule->isApplicable($criteria));
    }

    public function testProcessWhenOrganizationIsRequired(): void
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(1);

        /** @var Criteria|\PHPUnit\Framework\MockObject\MockObject $criteria */
        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->once())
            ->method('andExpression')
            ->with(new Comparison(new Path('organization', $criteria->getAlias()), Comparison::EQ, 1))
            ->willReturnSelf();

        $accessRule = new OrganizationAwareAccessRule($this->tokenAccessor);
        $accessRule->process($criteria);
    }

    public function testProcessWhenOrganizationIsOptional(): void
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(1);

        /** @var Criteria|\PHPUnit\Framework\MockObject\MockObject $criteria */
        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->once())
            ->method('andExpression')
            ->with(new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [
                    new Comparison(new Path('organization', $criteria->getAlias()), Comparison::EQ, 1),
                    new NullComparison(new Path('organization', $criteria->getAlias()))
                ]
            ))
            ->willReturnSelf();

        $accessRule = new OrganizationAwareAccessRule($this->tokenAccessor);
        $accessRule->setOrganizationOptional(true);
        $accessRule->process($criteria);
    }
}
