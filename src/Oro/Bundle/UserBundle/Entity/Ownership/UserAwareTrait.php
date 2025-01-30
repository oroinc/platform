<?php

namespace Oro\Bundle\UserBundle\Entity\Ownership;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\UserBundle\Entity\User;

/**
* UserAware trait
*
*/
trait UserAwareTrait
{
    use OrganizationAwareTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_owner_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $owner = null;

    /**
     * @return User|null
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User|null $owner
     * @return $this
     */
    public function setOwner(?User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }
}
