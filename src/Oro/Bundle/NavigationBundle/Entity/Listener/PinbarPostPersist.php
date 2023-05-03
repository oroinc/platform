<?php

namespace Oro\Bundle\NavigationBundle\Entity\Listener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\NavigationBundle\Entity\AbstractPinbarTab;

/**
 * Listens to AbstractPinbarTab Entity event to increment tabs positions
 */
class PinbarPostPersist
{
    public function postPersist(AbstractPinbarTab $pinbarTab, LifecycleEventArgs $args)
    {
        /** @var $repo \Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository */
        $repo = $args->getEntityManager()->getRepository(ClassUtils::getClass($pinbarTab));
        $repo->incrementTabsPositions(
            $pinbarTab->getItem()->getUser(),
            $pinbarTab->getItem()->getId(),
            $pinbarTab->getItem()->getOrganization()
        );
    }
}
