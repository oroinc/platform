<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\UserBundle\Security\UserChecker;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;

class UserCheckerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userChecker;

    /**
     * @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContextLink;

    /**
     * @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $flashBag;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $service;

    protected function setUp()
    {
        $this->service = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContextLink = $this->getMockBuilder(
            'Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContextLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->service);

        $this->flashBag = $this->getMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->userChecker = new UserChecker($this->securityContextLink, $this->flashBag, $translator);
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
        $this->service->expects($this->exactly($getTokenCalls))
            ->method('getToken')
            ->willReturn($token);

        if ($exceptionThrown) {
            $this->flashBag->expects($this->once())
                ->method('add')
                ->with('error', 'oro.user.security.password_changed.message');

            $this->setExpectedException(
                'Oro\Bundle\UserBundle\Exception\PasswordChangedException',
                'Invalid password.'
            );
        }

        $this->userChecker->checkPreAuth($user);
    }

    public function checkPreAuthProvider()
    {
        $data = [];

        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
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
}
