<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\EventListener;

use Oro\Bundle\MessageQueueBundle\Consumption\ConsumerHeartbeat;
use Oro\Bundle\MessageQueueBundle\EventListener\LoginListener;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Translation\TranslatorInterface;

class LoginListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoginListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $consumerHeartbeat;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var FlashBag */
    protected $flashBag;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    protected function setUp()
    {
        $this->consumerHeartbeat = $this->createMock(ConsumerHeartbeat::class);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($string) {
                    return $string . '|translated';
                }
            );

        $this->flashBag = new FlashBag();
        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getFlashBag')
            ->willReturn($this->flashBag);
        $this->request = $this->createMock(Request::class);
        $this->request->expects($this->any())
            ->method('getSession')
            ->willReturn($session);

        $this->listener = new LoginListener(
            $this->consumerHeartbeat,
            $this->translator,
            15
        );
    }

    public function testOnLoginWithTurnedOffFunctionality()
    {
        $listener = new LoginListener(
            $this->consumerHeartbeat,
            $this->translator,
            0
        );

        $this->consumerHeartbeat->expects($this->never())
            ->method('isAlive');

        $token = new UsernamePasswordToken('anon', '', 'key', ['anon']);
        $event = new InteractiveLoginEvent($this->request, $token);

        $listener->onLogin($event);

        $this->assertEmpty($this->flashBag->get('error'));
    }

    public function testOnLoginWithAnonUser()
    {
        $token = new UsernamePasswordToken('anon', '', 'key', ['anon']);
        $event = new InteractiveLoginEvent($this->request, $token);

        $this->listener->onLogin($event);

        $this->assertEmpty($this->flashBag->get('error'));
    }

    public function testOnLoginWithNotOutdatedState()
    {
        $user = new User();
        $token = new UsernamePasswordToken($user, '', 'key', ['user']);
        $event = new InteractiveLoginEvent($this->request, $token);

        $this->consumerHeartbeat->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $this->listener->onLogin($event);

        $this->assertEmpty($this->flashBag->get('error'));
    }

    public function testOnLoginWithOutdatedState()
    {
        $user = new User();
        $token = new UsernamePasswordToken($user, '', 'key', ['user']);
        $event = new InteractiveLoginEvent($this->request, $token);

        $this->consumerHeartbeat->expects($this->once())
            ->method('isAlive')
            ->willReturn(false);

        $this->listener->onLogin($event);

        $errors = $this->flashBag->get('error');
        $this->assertNotEmpty($errors);
        $this->assertEquals('oro.message_queue_job.no_alive_consumers|translated', $errors[0]);
    }
}
