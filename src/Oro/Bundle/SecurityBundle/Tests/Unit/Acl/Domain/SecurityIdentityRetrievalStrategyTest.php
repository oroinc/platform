<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityRetrievalStrategy;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;

class SecurityIdentityRetrievalStrategyTest extends \PHPUnit_Framework_TestCase
{
    const BUSINESS_UNIT_ID = 2;

    public function testGetSecurityIdentities()
    {
        $roleHierarchy = $this->getMockBuilder('Symfony\Component\Security\Core\Role\RoleHierarchyInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $roleHierarchy->expects($this->once())
            ->method('getReachableRoles')
            ->willReturn([]);
        $authenticationTrustResolver = $this->getMockBuilder(
            'Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver'
        )->disableOriginalConstructor()->getMock();
        $authenticationTrustResolver->expects($this->once())
            ->method('isFullFledged')
            ->willReturn(false);
        $authenticationTrustResolver->expects($this->once())
            ->method('isRememberMe')
            ->willReturn(false);
        $authenticationTrustResolver->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(false);
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getUsername')
            ->willReturn('');
        $businessUnit = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnitInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $businessUnit->expects($this->once())
            ->method('getId')
            ->willReturn(self::BUSINESS_UNIT_ID);
        $user->expects($this->once())
            ->method('getBusinessUnits')
            ->willReturn([$businessUnit]);
        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getRoles')
            ->willReturn([]);
        $token->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($user);
        $strategy = new SecurityIdentityRetrievalStrategy($roleHierarchy, $authenticationTrustResolver);
        $sids = $strategy->getSecurityIdentities($token);

        $this->assertCount(1, $sids);
        list($businessUnitSid) = $sids;
        $this->assertTrue($businessUnitSid instanceof BusinessUnitSecurityIdentity);
        $this->assertEquals(self::BUSINESS_UNIT_ID, $businessUnitSid->getId());
    }
}
