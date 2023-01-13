<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Exception\CredentialsResetException;
use Oro\Bundle\UserBundle\Exception\EmptyOwnerException;
use Oro\Bundle\UserBundle\Exception\OrganizationException;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;
use Oro\Bundle\UserBundle\Security\UserChecker;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UserCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var UserChecker */
    private $userChecker;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->userChecker = new UserChecker($this->tokenStorage);
    }

    private function getAuthStatus(string $id): AbstractEnumValue
    {
        $authStatus = $this->createMock(AbstractEnumValue::class);
        $authStatus->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $authStatus;
    }

    public function testCheckPostAuthForNotUser(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->userChecker->checkPostAuth($user);
    }

    public function testCheckPostAuthForUser(): void
    {
        $user = new User();
        $user->setAuthStatus($this->getAuthStatus(UserManager::STATUS_ACTIVE));
        $user->setOwner(new BusinessUnit());
        $organization = new Organization();
        $organization->setEnabled(true);
        $user->addOrganization($organization);

        $this->userChecker->checkPostAuth($user);
    }

    public function testCheckPostAuthForDisabledUser(): void
    {
        $this->expectException(DisabledException::class);

        $user = new User();
        $user->setEnabled(false);

        $this->userChecker->checkPostAuth($user);
    }

    public function testCheckPostAuthForUserWithoutAuthStatus(): void
    {
        $user = new User();
        $user->setOwner(new BusinessUnit());

        $this->userChecker->checkPostAuth($user);
    }

    public function testCheckPostAuthForUserInDisabledOrganization(): void
    {
        $this->expectException(OrganizationException::class);

        $user = new User();
        $user->setAuthStatus($this->getAuthStatus(UserManager::STATUS_ACTIVE));
        $user->setOwner(new BusinessUnit());
        $organization = new Organization();
        $organization->setEnabled(false);
        $user->addOrganization($organization);

        $this->userChecker->checkPostAuth($user);
    }

    public function testCheckPostAuthForUserNotBelongsToAnyOrganization(): void
    {
        $this->expectException(OrganizationException::class);

        $user = new User();
        $user->setAuthStatus($this->getAuthStatus(UserManager::STATUS_ACTIVE));
        $user->setOwner(new BusinessUnit());

        $this->userChecker->checkPostAuth($user);
    }

    public function testCheckPostAuthForUserWithoutOwner(): void
    {
        $this->expectException(EmptyOwnerException::class);

        $user = new User();
        $user->setAuthStatus($this->getAuthStatus(UserManager::STATUS_ACTIVE));
        $organization = new Organization();
        $organization->setEnabled(true);
        $user->addOrganization($organization);

        $this->userChecker->checkPostAuth($user);
    }

    public function testCheckPreAuthForNotUser(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->tokenStorage->expects(self::never())
            ->method('getToken');

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthForUserWithNotChangedPassword(): void
    {
        $user = new User();
        $user->setAuthStatus($this->getAuthStatus(UserManager::STATUS_ACTIVE));

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthForUserWithPasswordChangedBeforeLastLogin(): void
    {
        $user = new User();
        $user->setAuthStatus($this->getAuthStatus(UserManager::STATUS_ACTIVE));
        $user->setPasswordChangedAt(new \DateTime());
        $user->setLastLogin((new \DateTime())->modify('+1 minute'));

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));

        $this->userChecker->checkPreAuth($user);
    }


    public function testCheckPreAuthForUserWithPasswordChangedAfterLastLogin(): void
    {
        $this->expectException(PasswordChangedException::class);

        $user = new User();
        $user->setAuthStatus($this->getAuthStatus(UserManager::STATUS_ACTIVE));
        $user->setPasswordChangedAt(new \DateTime());
        $user->setLastLogin((new \DateTime())->modify('-1 minute'));

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthForUserWithoutAuthStatus(): void
    {
        $user = new User();

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthForUserWithActiveAuthStatus(): void
    {
        $user = new User();
        $user->setAuthStatus($this->getAuthStatus(UserManager::STATUS_ACTIVE));

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthForUserWithExpiredAuthStatus(): void
    {
        $this->expectException(CredentialsResetException::class);

        $user = new User();
        $user->setAuthStatus($this->getAuthStatus(UserManager::STATUS_RESET));

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->userChecker->checkPreAuth($user);
    }
}
