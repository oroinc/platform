<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\UserBundle\Security\UserChecker;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\OrganizationStub;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UserCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $userChecker;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenStorage;

    /** @var FlashBagInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $flashBag;

    protected function setUp()
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->userChecker = new UserChecker($this->tokenStorage, $this->flashBag, $translator);
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
            $this->flashBag->expects($this->once())
                ->method('add')
                ->with('error', 'oro.user.security.password_changed.message');

            $this->expectException('Oro\Bundle\UserBundle\Exception\PasswordChangedException');
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
            $this->expectException('Oro\Bundle\UserBundle\Exception\OrganizationException');
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
        $data[] = [
            'user' => $user1,
            'getTokenCalls' => 1,
            'token' => null,
            'exceptionThrown' => false,
        ];

        $user2 = new User();
        $user2->setPasswordChangedAt(new \DateTime());
        $user2->setLastLogin((new \DateTime())->modify('+1 minute'));
        $data[] = [
            'user' => $user2,
            'getTokenCalls' => 1,
            'token' => 'not_null',
            'exceptionThrown' => false,
        ];

        $user3 = new User();
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
        $user1->addOrganization($organization);
        $authStatus = $this->createMock('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue');
        $user1->setAuthStatus($authStatus);
        $data['with_organization'] = [
            'user' => $user1,
            'exceptionThrown' => false,
        ];

        $user2 = new User();
        $authStatus = $this->createMock('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue');
        $user2->setAuthStatus($authStatus);
        $data['without_organization'] = [
            'user' => $user2,
            'exceptionThrown' => true,
        ];

        return $data;
    }
}
