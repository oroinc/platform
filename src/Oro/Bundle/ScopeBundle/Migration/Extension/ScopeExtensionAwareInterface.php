<?php

namespace Oro\Bundle\ScopeBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see ScopeExtension}.
 */
interface ScopeExtensionAwareInterface
{
    public function setScopeExtension(ScopeExtension $scopeExtension);
}
