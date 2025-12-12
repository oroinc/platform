<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\EventListener\UserOrganizationListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserOrganizationListenerTest extends TestCase
{
    private TokenAccessorInterface|MockObject $tokenAccessor;

    private OrganizationAwareTokenInterface|MockObject $token;

    private UserOrganizationListener $listener;

    protected function setUp(): void
    {
        $this->token = $this->createMock(OrganizationAwareTokenInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor->expects(self::any())->method('getToken')->willReturn($this->token);

        $this->listener = new UserOrganizationListener($this->tokenAccessor);
    }

    /** @dataProvider userDataProvider */
    public function testPrePersist(User $user, ?Organization $organization): void
    {
        $this->token->expects(self::any())->method('getOrganization')->willReturn($organization);
        $this->listener->prePersist($user);

        self::assertEquals($user->getOrganization(), $user->getOrganizations()->first());
    }

    /** @dataProvider userDataProvider */
    public function testPreUpdate(User $user, ?Organization $organization): void
    {
        $this->token->expects(self::any())->method('getOrganization')->willReturn($organization);
        $this->listener->preUpdate($user);

        self::assertEquals($user->getOrganization(), $user->getOrganizations()->first());
    }

    public function userDataProvider(): array
    {
        $user1 = new User();
        $user2 = new User();
        $user3 = new User();
        $organization = new Organization();
        $organization2 = new Organization();

        $user1->setOrganization($organization);

        $user3->setOrganization($organization);
        $user3->addOrganization($organization);
        $user3->addOrganization($organization2);

        return [
            'User with organization and without added organizations' => [
                'user' => $user1,
                'organization' => $organization,
            ],
            'User without organization and without added organizations' => [
                'user' => $user2,
                'organization' => $organization,
            ],
            'User with organization and with added organizations' => [
                'user' => $user3,
                'organization' => $organization,
            ],
        ];
    }
}
