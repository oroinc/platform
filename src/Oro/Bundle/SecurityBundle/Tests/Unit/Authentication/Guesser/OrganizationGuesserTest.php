<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Guesser;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;

class OrganizationGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrganizationGuesser */
    private $guesser;

    protected function setUp(): void
    {
        $this->guesser = new OrganizationGuesser();
    }

    public function testGuessFromToken()
    {
        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);

        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->assertSame($organization, $this->guesser->guess($user, $token));
    }

    public function testGuessFromUserOrganization()
    {
        $user = $this->createMock(User::class);
        $userOrganization = $this->createMock(Organization::class);

        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $user->expects($this->once())
            ->method('getOrganization')
            ->willReturn($userOrganization);
        $user->expects($this->once())
            ->method('getOrganizations')
            ->with($this->isTrue())
            ->willReturn(new ArrayCollection([$userOrganization]));

        $this->assertSame($userOrganization, $this->guesser->guess($user, $token));
    }

    public function testGuessFromUserOrganizations()
    {
        $user = $this->createMock(User::class);
        $userOrganization = $this->createMock(Organization::class);
        $organization1 = $this->createMock(Organization::class);
        $organization2 = $this->createMock(Organization::class);

        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $user->expects($this->once())
            ->method('getOrganization')
            ->willReturn($userOrganization);
        $user->expects($this->once())
            ->method('getOrganizations')
            ->with($this->isTrue())
            ->willReturn(new ArrayCollection([$organization1, $organization2]));

        $this->assertSame($organization1, $this->guesser->guess($user, $token));
    }

    public function testGuessFromUserOrganizationsWhenTheyAreEmpty()
    {
        $user = $this->createMock(User::class);
        $userOrganization = $this->createMock(Organization::class);

        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $user->expects($this->once())
            ->method('getOrganization')
            ->willReturn($userOrganization);
        $user->expects($this->once())
            ->method('getOrganizations')
            ->with($this->isTrue())
            ->willReturn(new ArrayCollection());

        $this->assertNull($this->guesser->guess($user, $token));
    }
}
