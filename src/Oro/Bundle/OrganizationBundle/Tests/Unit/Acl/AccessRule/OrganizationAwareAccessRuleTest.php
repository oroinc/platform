<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\OrganizationBundle\Acl\AccessRule\OrganizationAwareAccessRule;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;

class OrganizationAwareAccessRuleTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrganizationRestrictionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationRestrictionProvider;

    protected function setUp(): void
    {
        $this->organizationRestrictionProvider = $this->createMock(OrganizationRestrictionProviderInterface::class);
    }

    public function testIsApplicable(): void
    {
        $accessRule = new OrganizationAwareAccessRule($this->organizationRestrictionProvider);
        $this->assertTrue($accessRule->isApplicable($this->createMock(Criteria::class)));
    }

    public function testProcess(): void
    {
        $criteria = $this->createMock(Criteria::class);

        $this->organizationRestrictionProvider->expects($this->once())
            ->method('applyOrganizationRestrictionsToAccessRuleCriteria')
            ->with(self::identicalTo($criteria), self::isNull(), 'organization');

        $accessRule = new OrganizationAwareAccessRule($this->organizationRestrictionProvider);
        $accessRule->process($criteria);
    }

    public function testProcessWithCustomOrganizationFieldName(): void
    {
        $organizationFieldName = 'testOrganization';
        $criteria = $this->createMock(Criteria::class);

        $this->organizationRestrictionProvider->expects($this->once())
            ->method('applyOrganizationRestrictionsToAccessRuleCriteria')
            ->with(self::identicalTo($criteria), self::isNull(), $organizationFieldName);

        $accessRule = new OrganizationAwareAccessRule($this->organizationRestrictionProvider, $organizationFieldName);
        $accessRule->process($criteria);
    }
}
