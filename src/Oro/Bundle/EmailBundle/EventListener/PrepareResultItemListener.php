<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Symfony\Component\Routing\Router;

use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;

class PrepareResultItemListener
{
    const EMAIL_USER_CLASS_NAME = 'Oro\Bundle\EmailBundle\Entity\EmailUser';
    const EMAIL_CLASS_NAME = 'Oro\Bundle\EmailBundle\Entity\Email';

    /** @var Router */
    protected $router;

    /**
     * Constructor
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Change search results view for Email entity
     *
     * @param PrepareResultItemEvent $event
     */
    public function prepareEmailItemDataEvent(PrepareResultItemEvent $event)
    {
        if ($event->getResultItem()->getEntityName() === self::EMAIL_USER_CLASS_NAME) {
            $id = $event->getResultItem()->getEntity()->getEmail()->getId();
            $event->getResultItem()->setRecordId($id);
            $event->getResultItem()->setEntityName(self::EMAIL_CLASS_NAME);
            $route = $this->router->generate('oro_email_thread_view', ['id' => $id], true);
            $event->getResultItem()->setRecordUrl($route);
        }
    }
}
