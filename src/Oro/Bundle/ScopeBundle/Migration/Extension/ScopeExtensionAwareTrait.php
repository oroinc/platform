<?php

namespace Oro\Bundle\ScopeBundle\Migration\Extension;

trait ScopeExtensionAwareTrait
{
    /** @var ScopeExtension */
    protected $scopeExtension;

    /**
     * @param ScopeExtension $scopeExtension
     */
    public function setScopeExtension(ScopeExtension $scopeExtension)
    {
        $this->scopeExtension = $scopeExtension;
    }
}
