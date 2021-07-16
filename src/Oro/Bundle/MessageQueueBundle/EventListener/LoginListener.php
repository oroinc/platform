<?php

namespace Oro\Bundle\MessageQueueBundle\EventListener;

use Oro\Bundle\MessageQueueBundle\Consumption\ConsumerHeartbeat;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener that checks that consumers are alive upon a user login.
 */
class LoginListener
{
    /** @var ConsumerHeartbeat */
    private $consumerHeartbeat;

    /** @var TranslatorInterface */
    private $translator;

    /** @var int */
    private $updateHeartbeatPeriod;

    /**
     * LoginListener constructor.
     *
     * @param ConsumerHeartbeat   $consumerHeartbeat
     * @param TranslatorInterface $translator
     * @param int                 $updateHeartbeatPeriod
     */
    public function __construct(
        ConsumerHeartbeat $consumerHeartbeat,
        TranslatorInterface $translator,
        $updateHeartbeatPeriod
    ) {
        $this->consumerHeartbeat = $consumerHeartbeat;
        $this->translator = $translator;
        $this->updateHeartbeatPeriod = $updateHeartbeatPeriod;
    }

    /**
     * Checks that consumers are alive upon a user login and adds the flash message if there are no alive consumers.
     */
    public function onLogin(InteractiveLoginEvent $event)
    {
        // do nothing if the check was disabled with 0 config option value
        if ($this->updateHeartbeatPeriod === 0) {
            return;
        }

        $token = $event->getAuthenticationToken();
        if ($token instanceof UsernamePasswordToken
            && $token->getUser() instanceof UserInterface
            && !$this->consumerHeartbeat->isAlive()
        ) {
            $event->getRequest()->getSession()->getFlashBag()->add(
                'error',
                $this->translator->trans('oro.message_queue_job.no_alive_consumers')
            );
        }
    }
}
