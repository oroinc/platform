<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateChangeEvent;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateWithScopeChangeEvent;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Flush menu update cache.
 */
class MenuUpdateCacheFlusher
{
    private CacheInterface $cache;
    private MenuUpdateRepository $repository;
    protected ScopeManager $scopeManager;
    protected string $scopeType;

    /**
     * @param MenuUpdateRepository $repository
     * @param CacheInterface $cache
     * @param ScopeManager $scopeManager
     * @param string $scopeType
     */
    public function __construct(
        MenuUpdateRepository $repository,
        CacheInterface $cache,
        ScopeManager $scopeManager,
        $scopeType
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->scopeManager = $scopeManager;
        $this->scopeType = $scopeType;
    }

    public function onMenuUpdateScopeChange(MenuUpdateChangeEvent $event)
    {
        $scope = $this->scopeManager->find($this->scopeType, $event->getContext());
        $this->flushCaches($event->getMenuName(), $scope);
    }

    public function onMenuUpdateWithScopeChange(MenuUpdateWithScopeChangeEvent $event)
    {
        $this->flushCaches($event->getMenuName(), $event->getScope());
    }

    protected function flushCaches(string $menuName, ?Scope $scope): void
    {
        if (null !== $scope) {
            $this->cache->delete(MenuUpdateUtils::generateKey($menuName, $scope));
            $this->repository->findMenuUpdatesByScope($menuName, $scope);
        }
    }
}
