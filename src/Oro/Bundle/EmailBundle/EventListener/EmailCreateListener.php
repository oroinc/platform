<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Event\EmailCreated;
use Oro\Bundle\EmailBundle\Manager\EmailContextManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailCreateListener
{
    /** @var EmailContextManager */
    protected $contextManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param EmailContextManager $contextManager
     * @param ServiceLink $securityFacadeLink
     */
    public function __construct(
        EmailContextManager $contextManager,
        ServiceLink $securityFacadeLink
    ) {
        $this->contextManager = $contextManager;
        $this->securityFacade = $securityFacadeLink->getService();
    }

    /**
     * @param EmailCreated $event
     */
    public function processContextEvent(EmailCreated $event)
    {
//        if (!$this->securityFacade->isGranted('EDIT', 'entity:' . Email::ENTITY_CLASS)) {
//            return;
//        }
        if ($event->getEmail()->getThread()) {
            $this->contextManager->addContextsToThread($event->getEmail());
        }
    }
}
