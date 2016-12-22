<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class MenuScopeHelper
{
    /** @var ScopeManager */
    protected $scopeManager;

    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    public function getMenuContext()
    {

    }

}
