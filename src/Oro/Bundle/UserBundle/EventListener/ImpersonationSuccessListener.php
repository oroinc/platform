<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\UserBundle\Event\ImpersonationSuccessEvent;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Listens to successful impersonation logins and sends email to the impersonated user
 */
class ImpersonationSuccessListener
{
    /** @var Processor */
    protected $mailProcessor;

    /** @var FlashBagInterface */
    protected $flashBag;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        Processor $mailProcessor,
        FlashBagInterface $flashBag,
        LoggerInterface $logger
    ) {
        $this->mailProcessor = $mailProcessor;
        $this->flashBag = $flashBag;
        $this->logger = $logger;
    }

    public function onImpersonationSuccess(ImpersonationSuccessEvent $event)
    {
        if (!$event->getImpersonation()->hasNotify()) {
            return;
        }

        try {
            $this->mailProcessor->sendImpersonateEmail($event->getImpersonation()->getUser());
        } catch (\Exception $e) {
            $this->flashBag->add('error', 'oro.user.impersonation.notification_error');
            $this->logger->error($e->getMessage());
        }
    }
}
