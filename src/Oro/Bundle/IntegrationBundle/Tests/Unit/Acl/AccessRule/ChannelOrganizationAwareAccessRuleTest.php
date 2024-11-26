<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\IntegrationBundle\Acl\AccessRule\ChannelOrganizationAwareAccessRule;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChannelOrganizationAwareAccessRuleTest extends TestCase
{
    private ChannelOrganizationAwareAccessRule $rule;
    private TokenAccessorInterface|MockObject $tokenAccessor;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->rule = new ChannelOrganizationAwareAccessRule($this->tokenAccessor, 'organization');
    }

    public function testIsApplicable(): void
    {
        $criteria = $this->createMock(Criteria::class);
        $criteria
            ->expects(self::exactly(2))
            ->method('getEntityClass')
            ->willReturnOnConsecutiveCalls(
                Channel::class,
                TestActivity::class
            );

        $this->assertTrue($this->rule->isApplicable($criteria));
        $this->assertFalse($this->rule->isApplicable($criteria));
    }

    public function testProcessWithOrganization(): void
    {
        $this->tokenAccessor
            ->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(1);

        $criteria = $this->createMock(Criteria::class);
        $criteria
            ->expects(self::once())
            ->method('andExpression')
            ->with(new Comparison(
                new Path('organization', $criteria->getAlias()),
                Comparison::EQ,
                1
            ));

        $this->rule->process($criteria);
    }

    public function testProcessWithoutOrganizations(): void
    {
        $this->tokenAccessor
            ->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(null);

        $criteria = $this->createMock(Criteria::class);
        $criteria
            ->expects(self::once())
            ->method('andExpression')
            ->with(new AccessDenied());

        $this->rule->process($criteria);
    }
}
