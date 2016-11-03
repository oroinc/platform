<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\EventListener\PasswordExpirySubscriber;

class PasswordExpirySubscriberTest extends \PHPUnit_Framework_TestCase
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
        $expiryDate = $now->add(new \DateInterval('P'.($daysToPasswordExpiry - 1).'D'));
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
     * @return PasswordExpirySubscriber
     */
    private function getSubscriber($daysToExpiry = 0)
    {
        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        if (in_array($daysToExpiry, PasswordExpirySubscriber::$periodMarkers)) {
            $flashBag = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface')
                ->getMock();
            $flashBag->expects($this->once())
                ->method('add');

            $session->expects($this->once())
                ->method('getFlashBag')
                ->willReturn($flashBag);
        }
        
        return new PasswordExpirySubscriber($session, $translator);
    }

    public function getDaysToPasswordExpiry()
    {
        return [array_merge([2,4,5,6,8], PasswordExpirySubscriber::$periodMarkers)];
    }
}
