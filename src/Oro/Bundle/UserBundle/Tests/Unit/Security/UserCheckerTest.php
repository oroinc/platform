<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Exception\EmptyOwnerException;
use Oro\Bundle\UserBundle\Exception\OrganizationException;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;
use Oro\Bundle\UserBundle\Security\UserChecker;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\OrganizationStub;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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

    /**
     * @dataProvider checkPreAuthProvider
     */
    public function testCheckPreAuth(UserInterface $user, int $getTokenCalls, ?string $token, bool $exceptionThrown)
    {
        $this->tokenStorage->expects($this->exactly($getTokenCalls))
            ->method('getToken')
            ->willReturn($token);

        if ($exceptionThrown) {
            $this->expectException(PasswordChangedException::class);
            $this->expectExceptionMessage('Invalid password.');
        }

        $this->userChecker->checkPreAuth($user);
    }

    /**
     * @dataProvider checkPostAuthProvider
     */
    public function testCheckPostAuth(UserInterface $user, bool $exceptionThrown)
    {
        if ($exceptionThrown) {
            $this->expectException(OrganizationException::class);
            $this->expectExceptionMessage('');
        }

        $this->userChecker->checkPostAuth($user);
    }

    public function checkPreAuthProvider(): array
    {
        $data = [];

        $user = $this->createMock(UserInterface::class);
        $data[] = [
            'user' => $user,
            'getTokenCalls' => 0,
            'token' => null,
            'exceptionThrown' => false,
        ];

        $user1 = new User();
        $user1->setOwner(new BusinessUnit());
        $data[] = [
            'user' => $user1,
            'getTokenCalls' => 1,
            'token' => null,
            'exceptionThrown' => false,
        ];

        $user2 = new User();
        $user2->setOwner(new BusinessUnit());
        $user2->setPasswordChangedAt(new \DateTime());
        $user2->setLastLogin((new \DateTime())->modify('+1 minute'));
        $data[] = [
            'user' => $user2,
            'getTokenCalls' => 1,
            'token' => 'not_null',
            'exceptionThrown' => false,
        ];

        $user3 = new User();
        $user3->setOwner(new BusinessUnit());
        $passwordChangedAt = new \DateTime();
        $lastLogin = clone $passwordChangedAt;
        $user3->setPasswordRequestedAt($passwordChangedAt);
        $user3->setLastLogin($lastLogin);
        $data[] = [
            'user' => $user3,
            'getTokenCalls' => 1,
            'token' => 'not_null',
            'exceptionThrown' => false,
        ];

        $user4 = new User();
        $user4->setOwner(new BusinessUnit());
        $user4->setPasswordChangedAt(new \DateTime());
        $user4->setLastLogin((new \DateTime())->modify('-1 minute'));
        $data[] = [
            'user' => $user4,
            'getTokenCalls' => 1,
            'token' => 'not_null',
            'exceptionThrown' => true,
        ];

        return $data;
    }

    public function checkPostAuthProvider(): array
    {
        $data = [];

        $user = $this->createMock(UserInterface::class);
        $data['invalid_user_class'] = [
            'user' => $user,
            'exceptionThrown' => false,
        ];

        $organization = new OrganizationStub();
        $organization->setEnabled(true);
        $user1 = new User();
        $user1->setOwner($this->createMock(BusinessUnit::class));
        $user1->addOrganization($organization);
        $authStatus = $this->createMock(AbstractEnumValue::class);
        $user1->setAuthStatus($authStatus);
        $data['with_organization'] = [
            'user' => $user1,
            'exceptionThrown' => false,
        ];

        $user2 = new User();
        $user2->setOwner($this->createMock(BusinessUnit::class));
        $authStatus = $this->createMock(AbstractEnumValue::class);
        $user2->setAuthStatus($authStatus);
        $data['without_organization'] = [
            'user' => $user2,
            'exceptionThrown' => true,
        ];

        return $data;
    }

    public function testCheckPostAuthOnUserWithoutOwner()
    {
        $this->expectException(EmptyOwnerException::class);
        $user = new User();

        $this->userChecker->checkPostAuth($user);
    }
}
