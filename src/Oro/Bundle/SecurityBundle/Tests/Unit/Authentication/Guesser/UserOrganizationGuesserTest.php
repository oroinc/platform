<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Guesser;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\UserOrganizationGuesser;

class UserOrganizationGuesserTest extends \PHPUnit\Framework\TestCase
{
    public function testGuessFromToken()
    {
        $guesser = new UserOrganizationGuesser();

        $user         = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()->getMock();
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()->getMock();

        $token = $this->createMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
        $token->expects($this->exactly(2))->method('getOrganizationContext')->willReturn($organization);

        $this->assertSame($organization, $guesser->guess($user, $token));
    }

    public function guessFromUserCreatorOrganizationEvenIfEmptyKnownTokenGiven()
    {
        $guesser = new UserOrganizationGuesser();

        $user                = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()->getMock();
        $creatorOrganization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()->getMock();

        $token = $this->createMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
        $token->expects($this->once())->method('getOrganizationContext')->willReturn(false);

        $user->expects($this->once())->method('getOrganization')->willReturn($creatorOrganization);
        $user->expects($this->once())->method('getOrganizations')->with(true)
            ->willReturn(new ArrayCollection([$creatorOrganization]));

        $this->assertSame($creatorOrganization, $guesser->guess($user, $token));
    }

    public function guessFromUserActiveOrganizations()
    {
        $guesser = new UserOrganizationGuesser();

        $user                = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()->getMock();
        $creatorOrganization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()->getMock();
        $organization1 = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()->getMock();
        $organization2 = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()->getMock();

        $token = $this->createMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
        $token->expects($this->once())->method('getOrganizationContext')->willReturn(false);

        $user->expects($this->once())->method('getOrganization')->willReturn($creatorOrganization);
        $user->expects($this->once())->method('getOrganizations')->with(true)
            ->willReturn(new ArrayCollection([$organization1, $organization2]));

        $this->assertSame($organization1, $guesser->guess($user, $token));
    }
}
