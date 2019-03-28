<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

/**
 * Provide menu items based on menu updates.
 */
class MenuUpdateProvider implements MenuUpdateProviderInterface
{
    const SCOPE_CONTEXT_OPTION = 'scopeContext';

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @var MenuUpdateManager
     */
    private $menuUpdateManager;

    /**
     * @var array
     */
    private $scopeIds = [];

    /**
     * @param ScopeManager $scopeManager
     * @param MenuUpdateManager $menuUpdateManager
     */
    public function __construct(ScopeManager $scopeManager, MenuUpdateManager $menuUpdateManager)
    {
        $this->scopeManager = $scopeManager;
        $this->menuUpdateManager = $menuUpdateManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getMenuUpdatesForMenuItem(ItemInterface $menuItem, array $options = [])
    {
        $scopeType = $menuItem->getExtra('scope_type', ConfigurationBuilder::DEFAULT_SCOPE_TYPE);
        if ($scopeType !== $this->menuUpdateManager->getScopeType()) {
            return [];
        }

        $scopeContext = $options[self::SCOPE_CONTEXT_OPTION] ?? null;
        $repo = $this->menuUpdateManager->getRepository();

        return $repo->findMenuUpdatesByScopeIds($menuItem->getName(), $this->getScopeIds($scopeType, $scopeContext));
    }

    /**
     * @param string $scopeType
     * @param array|object|null $context
     *
     * @return array
     */
    private function getScopeIds($scopeType, $context)
    {
        $scopeCacheKey = $scopeType . ':' . md5(serialize($context));
        if (!array_key_exists($scopeCacheKey, $this->scopeIds)) {
            $this->scopeIds[$scopeCacheKey] = $this->scopeManager->findRelatedScopeIdsWithPriority(
                $scopeType,
                $context
            );
        }

        return $this->scopeIds[$scopeCacheKey];
    }
}
