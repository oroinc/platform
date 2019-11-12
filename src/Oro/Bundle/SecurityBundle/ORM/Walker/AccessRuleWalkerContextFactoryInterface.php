<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

/**
 * Represents a factory to create AccessRuleWalkerContext object.
 */
interface AccessRuleWalkerContextFactoryInterface
{
    /**
     * @param string $permission
     *
     * @return AccessRuleWalkerContext
     */
    public function createContext(string $permission): AccessRuleWalkerContext;
}
