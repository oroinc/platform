<?php

namespace Oro\Bundle\ScopeBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface ScopeCollectionAwareInterface
{
    /**
     * @return Collection|Scope[]
     */
    public function getScopes();
}
