<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateScopeChangeEvent;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

class MenuUpdateCacheFlusher
{
    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * @var MenuUpdateRepository
     */
    private $repository;

    /**
     * @param MenuUpdateRepository $repository
     * @param CacheProvider $cache
     */
    public function __construct(MenuUpdateRepository $repository, CacheProvider $cache)
    {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /**
     * @param MenuUpdateScopeChangeEvent $event
     */
    public function onMenuUpdateScopeChange(MenuUpdateScopeChangeEvent $event)
    {
        $this->cache->delete(MenuUpdateUtils::generateKey($event->getMenuName(), $event->getScope()));
        $this->repository->findMenuUpdatesByScope($event->getMenuName(), $event->getScope());
    }
}
