<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateChangeEvent;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

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
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @param MenuUpdateRepository $repository
     * @param CacheProvider        $cache
     * @param ScopeManager         $scopeManager
     */
    public function __construct(MenuUpdateRepository $repository, CacheProvider $cache, ScopeManager $scopeManager)
    {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param MenuUpdateChangeEvent $event
     */
    public function onMenuUpdateScopeChange(MenuUpdateChangeEvent $event)
    {
        $scope = $this->scopeManager->find($event->getContext());
        if (null === $scope) {
            $this->cache->delete(MenuUpdateUtils::generateKey($event->getMenuName(), $scope));
            $this->repository->findMenuUpdatesByScope($event->getMenuName(), $scope);
        }
    }
}
