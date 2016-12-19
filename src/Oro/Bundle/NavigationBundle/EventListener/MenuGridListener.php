<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class MenuGridListener
{
    /** @var string */
    private $scopeType;

    /** @var ScopeManager  */
    private $scopeManager;

    const PATH_VIEW_LINK_ROUTE = '[properties][view_link][direct_params][route]';
    const PATH_VIEW_LINK_ID = '[properties][view_link][direct_params][scopeId]';

    /**
     * @param ScopeManager $scopeManager
     */
    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param $scopeType
     */
    public function setScopeType($scopeType)
    {
        $this->scopeType = $scopeType;
    }

    /**
     * Adds config on organization level to the organization grid
     *
     * @param PreBuild $event
     */
    public function onPreBefore(PreBuild $event)
    {
        $scope = $this->scopeManager->findOrCreate(
            $this->scopeType,
            $event->getParameters()->get('scopeContext')
        );

        $config = $event->getConfig();
        $config->offsetSetByPath(self::PATH_VIEW_LINK_ROUTE, $event->getParameters()->get('viewLinkRoute'));
        $config->offsetSetByPath(self::PATH_VIEW_LINK_ID, $scope->getId());
    }
}
