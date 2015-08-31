<?php

namespace Oro\Bundle\NavigationBundle\Entity\Listener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;

class PinbarPostPersist
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @param string $className
     *
     * @return PinbarPostPersist
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        /** @var $entity \Oro\Bundle\NavigationBundle\Entity\PinbarTab */
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

        // perhaps you only want to act on some "PinbarTab" entity
        if (is_a($entity, $this->className, true)) {
            /** @var $repo \Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository */
            $repo = $entityManager->getRepository(ClassUtils::getClass($entity));
            $repo->incrementTabsPositions(
                $entity->getItem()->getUser(),
                $entity->getItem()->getId(),
                $entity->getItem()->getOrganization()
            );
        }
    }
}
