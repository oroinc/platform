<?php

namespace Oro\Bundle\ScopeBundle\Entity;

interface ScopeAwareInterface
{
    /**
     * @return Scope
     */
    public function getScope();
}
