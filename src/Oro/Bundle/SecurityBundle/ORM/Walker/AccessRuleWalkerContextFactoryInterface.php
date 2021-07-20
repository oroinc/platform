<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

/**
 * Represents a factory to create AccessRuleWalkerContext object.
 */
interface AccessRuleWalkerContextFactoryInterface
{
    public function createContext(string $permission): AccessRuleWalkerContext;
}
