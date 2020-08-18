<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Security\UserChecker;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\OrganizationStub;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $userChecker;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenStorage;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->userChecker = new UserChecker($this->tokenStorage);
    }

    /**
     * @param UserInterface $user
     * @param int           $getTokenCalls
     * @param string        $token
     * @param boolean       $exceptionThrown
     *
     * @dataProvider checkPreAuthProvider
     */
    public function testCheckPreAuth(UserInterface $user, $getTokenCalls, $token, $exceptionThrown)
    {
        $this->tokenStorage->expects($this->exactly($getTokenCalls))
            ->method('getToken')
            ->willReturn($token);

        if ($exceptionThrown) {
            $this->expectException(\Oro\Bundle\UserBundle\Exception\PasswordChangedException::class);
            $this->expectExceptionMessage('Invalid password.');
        }

        $this->userChecker->checkPreAuth($user);
    }

    /**
     * @param UserInterface $user
     * @param boolean       $exceptionThrown
     *
     * @dataProvider checkPostAuthProvider
     */
    public function testCheckPostAuth(UserInterface $user, $exceptionThrown)
    {
        if ($exceptionThrown) {
            $this->expectException(\Oro\Bundle\UserBundle\Exception\OrganizationException::class);
            $this->expectExceptionMessage('');
        }

        $this->userChecker->checkPostAuth($user);
    }

    public function checkPreAuthProvider()
    {
        $data = [];

        $user = $this->createMock('Symfony\Component\Security\Core\User\UserInterface');
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

    public function checkPostAuthProvider()
    {
        $data = [];

        $user = $this->createMock('Symfony\Component\Security\Core\User\UserInterface');
        $data['invalid_user_class'] = [
            'user' => $user,
            'exceptionThrown' => false,
        ];

        $organization = new OrganizationStub();
        $organization->setEnabled(true);
        $user1 = new User();
        $user1->setOwner($this->createMock(BusinessUnit::class));
        $user1->addOrganization($organization);
        $authStatus = $this->createMock('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue');
        $user1->setAuthStatus($authStatus);
        $data['with_organization'] = [
            'user' => $user1,
            'exceptionThrown' => false,
        ];

        $user2 = new User();
        $user2->setOwner($this->createMock(BusinessUnit::class));
        $authStatus = $this->createMock('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue');
        $user2->setAuthStatus($authStatus);
        $data['without_organization'] = [
            'user' => $user2,
            'exceptionThrown' => true,
        ];

        return $data;
    }

    public function testCheckPostAuthOnUserWithoutOwner()
    {
        $this->expectException(\Oro\Bundle\UserBundle\Exception\EmptyOwnerException::class);
        $user = new User();

        $this->userChecker->checkPostAuth($user);
    }
}
