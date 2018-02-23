<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\ActivityBundle\Event\PrepareContextTitleEvent;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Routing\Router;

class PrepareContextTitleListener
{
    /** @var Router */
    protected $router;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param Router $router
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        Router $router,
        DoctrineHelper $doctrineHelper
    ) {
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Correct link and title for email context by EmailUser entity index
     *
     * @param PrepareContextTitleEvent $event
     */
    public function prepareEmailContextTitleEvent(PrepareContextTitleEvent $event)
    {
        if ($event->getTargetClass() === Email::ENTITY_CLASS) {
            $item = $event->getItem();
            /** @var Email $email */
            $email = $this->doctrineHelper->getEntity(Email::ENTITY_CLASS, $item['targetId']);
            $item['title'] = $email->getSubject();
            $item['link'] = $this->router->generate('oro_email_thread_view', ['id' => $item['targetId']], true);
            $event->setItem($item);
        }
    }
}
