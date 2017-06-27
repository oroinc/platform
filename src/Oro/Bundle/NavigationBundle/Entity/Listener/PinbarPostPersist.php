<?php

namespace Oro\Bundle\NavigationBundle\Entity\Listener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\NavigationBundle\Entity\AbstractPinbarTab;

class PinbarPostPersist
{
    /**
     * @param $pinbarTab
     * @param LifecycleEventArgs $args
     */
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
