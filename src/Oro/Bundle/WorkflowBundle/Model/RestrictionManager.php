<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;

class RestrictionManager
{
    /**
     * @var Collection
     */
    protected $restrictions;

    /**
     * @return Restriction[]|Collection
     */
    public function getRestrictions()
    {
        return $this->restrictions;
    }

    /**
     * @param Restriction[]|Collection $restrictions
     */
    public function setRestrictions($restrictions)
    {
        $this->restrictions = $restrictions;
    }
}
