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
     * @var string
     */
    protected $scopeType;

    /**
     * @param MenuUpdateRepository $repository
     * @param CacheProvider        $cache
     * @param ScopeManager         $scopeManager
     * @param string               $scopeType
     */
    public function __construct(
        MenuUpdateRepository $repository,
        CacheProvider $cache,
        ScopeManager $scopeManager,
        $scopeType
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->scopeManager = $scopeManager;
        $this->scopeType = $scopeType;
    }

    /**
     * @param MenuUpdateChangeEvent $event
     */
    public function onMenuUpdateScopeChange(MenuUpdateChangeEvent $event)
    {
        $scope = $this->scopeManager->find($this->scopeType, $event->getContext());
        if (null !== $scope) {
            $this->cache->delete(MenuUpdateUtils::generateKey($event->getMenuName(), $scope));
            $this->repository->findMenuUpdatesByScope($event->getMenuName(), $scope);
        }
    }
}
