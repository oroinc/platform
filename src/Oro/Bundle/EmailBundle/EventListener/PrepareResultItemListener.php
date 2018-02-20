<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Symfony\Component\Routing\Router;

class PrepareResultItemListener
{
    /** @var Router */
    protected $router;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param Router $router
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(Router $router, DoctrineHelper $doctrineHelper)
    {
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Change search results view for Email entity
     *
     * @param PrepareResultItemEvent $event
     */
    public function prepareEmailItemDataEvent(PrepareResultItemEvent $event)
    {
        if ($event->getResultItem()->getEntityName() === EmailUser::ENTITY_CLASS) {
            $searchItem = $event->getResultItem();

            $id = $this
                ->doctrineHelper
                ->getEntityRepository(EmailUser::ENTITY_CLASS)
                ->find($searchItem->getId())
                ->getEmail()
                ->getId();

            $searchItem->setRecordId($id);
            $searchItem->setEntityName(Email::ENTITY_CLASS);
            $route = $this->router->generate('oro_email_thread_view', ['id' => $id], true);
            $searchItem->setRecordUrl($route);
        }
    }
}
