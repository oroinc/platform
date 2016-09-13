<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\UserBundle\Event\ImpersonationSuccessEvent;
use Oro\Bundle\UserBundle\Mailer\Processor;

/**
 * Listens to successful impersonation logins and sends email to the impersonated user
 */
class ImpersonationSuccessListener
{
    /** @var Processor */
    private $mailProcessor;

    /**
     * @param Processor $mailProcessor
     */
    public function __construct(Processor $mailProcessor)
    {
        $this->mailProcessor = $mailProcessor;
    }

    /**
     * @param ImpersonationSuccessEvent $event
     */
    public function onImpersonationSuccess(ImpersonationSuccessEvent $event)
    {
        $this->mailProcessor->sendImpersonateEmail($event->getUser());
    }
}
