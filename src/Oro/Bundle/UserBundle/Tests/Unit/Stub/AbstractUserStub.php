<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class AbstractUserStub extends AbstractUser
{
    /**
     * {@inheritDoc}
     */
    public function getOrganizations(bool $onlyEnabled = false)
    {
        $organizations = new ArrayCollection();
        if ($this->organization) {
            if (!$onlyEnabled || $this->organization->isEnabled()) {
                $organizations->add($this->organization);
            }
        }

        return $organizations;
    }
}
