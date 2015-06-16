<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

class EmailOriginListener
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $securityFacade = $this->container->get('oro_security.security_facade');
        if (!$securityFacade->hasLoggedUser()) {
            return;
        }

        $entity = $args->getEntity();
        if ($entity instanceof EmailOrigin) {
            if ($entity->getOrganization() === null) {
                $entity->setOrganization($securityFacade->getOrganization());
            }
            if ($entity->getOwner() === null) {
                $entity->setOwner($securityFacade->getLoggedUser());
            }
        }
    }
}
