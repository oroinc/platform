<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

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
     * @param ScopeManager      $scopeManager
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

        $scopeContext = array_key_exists(self::SCOPE_CONTEXT_OPTION, $options) ?
            $options[self::SCOPE_CONTEXT_OPTION] : null;
        $scopeIds = $this->scopeManager->findRelatedScopeIdsWithPriority($scopeType, $scopeContext);

        $repo = $this->menuUpdateManager->getRepository();

        return $repo->findMenuUpdatesByScopeIds($menuItem->getName(), $scopeIds);
    }
}
