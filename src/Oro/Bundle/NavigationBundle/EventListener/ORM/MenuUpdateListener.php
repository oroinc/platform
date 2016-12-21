<?php

namespace Oro\Bundle\NavigationBundle\EventListener\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

class MenuUpdateListener
{
    /**
     * @var MenuUpdateRepository
     */
    private $menuUpdateRepository;

    /**
     * @param MenuUpdateRepository $menuUpdateRepository
     */
    public function __construct(MenuUpdateRepository $menuUpdateRepository)
    {
        $this->menuUpdateRepository = $menuUpdateRepository;
    }

    /**
     * @param MenuUpdateInterface $update
     * @param LifecycleEventArgs $args
     */
    public function postPersist(MenuUpdateInterface $update, LifecycleEventArgs $args)
    {
        $this->resetAndWarmupResultCache($args->getEntityManager(), $update);
    }

    /**
     * @param MenuUpdateInterface $update
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(MenuUpdateInterface $update, LifecycleEventArgs $args)
    {
        $this->resetAndWarmupResultCache($args->getEntityManager(), $update);
    }

    /**
     * @param MenuUpdateInterface $update
     * @param LifecycleEventArgs $args
     */
    public function postRemove(MenuUpdateInterface $update, LifecycleEventArgs $args)
    {
        $this->resetAndWarmupResultCache($args->getEntityManager(), $update);
    }

    /**
     * @param EntityManagerInterface $em
     * @param MenuUpdateInterface $update
     */
    private function resetAndWarmupResultCache(EntityManagerInterface $em, MenuUpdateInterface $update)
    {
        $em->getConfiguration()->getResultCacheImpl()->delete(
            MenuUpdateUtils::generateKey($update->getMenu(), $update->getScope())
        );

        $this->menuUpdateRepository->findMenuUpdatesByScope($update->getMenu(), $update->getScope());
    }
}
