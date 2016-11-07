<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\EventListener\PasswordExpiryWarnSubscriber;
use Oro\Bundle\UserBundle\Provider\PasswordChangePeriodConfigProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class PasswordExpiryWarnSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var User */
    protected $user;

    public function setUp()
    {
        $this->user = new User();
    }

    /**
     * @dataProvider getDaysToPasswordExpiry
     */
    public function testPasswordExpirationMessage($daysToPasswordExpiry)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $expiryDate = $now->add(new \DateInterval('P'.$daysToPasswordExpiry.'D'));
        $this->user->setPasswordExpiresAt($expiryDate);
        $subscriber = $this->getSubscriber($daysToPasswordExpiry);
        $subscriber->onInteractiveLogin($this->getInteractiveLoginEvent($this->user));
    }

    /**
     * @param object $user
     * @return InteractiveLoginEvent
     */
    private function getInteractiveLoginEvent($user)
    {
        $token = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);

        $event = $this->getMockBuilder(InteractiveLoginEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())
            ->method('getAuthenticationToken')
            ->willReturn($token);

        return $event;
    }

    /**
     * @param int $daysToExpiry
     * @return PasswordExpiryWarnSubscriber
     */
    private function getSubscriber($daysToExpiry = 0)
    {
        $notificationDays = [1, 3, 7];
        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider = $this->getMockBuilder(PasswordChangePeriodConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider->expects($this->once())
            ->method('getNotificationDays')
            ->willReturn($notificationDays);

        if (in_array($daysToExpiry, $notificationDays)) {
            $flashBag = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface')
                ->getMock();
            $flashBag->expects($this->once())
                ->method('add');

            $session->expects($this->once())
                ->method('getFlashBag')
                ->willReturn($flashBag);
        }
        
        return new PasswordExpiryWarnSubscriber($configProvider, $session, $translator);
    }

    public function getDaysToPasswordExpiry()
    {
        return [
            [1, 2, 3, 4, 5, 6, 7, 8]
        ];
    }
}
