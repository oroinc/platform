<?php

namespace Oro\Bundle\EntityBundle\EntityProperty;

use Oro\Bundle\UserBundle\Entity\User;

trait UpdatedByAwareTrait
{
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="updated_by_user_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @var bool
     */
    protected $isUpdatedBySet;

    /**
     * @param User|null $updatedBy
     *
     * @return $this
     */
    public function setUpdatedBy(User $updatedBy = null)
    {
        $this->isUpdatedBySet = false;
        if ($updatedBy !== null) {
            $this->isUpdatedBySet = true;
        }

        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return User
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * @return bool
     */
    public function isUpdatedBySet()
    {
        return $this->isUpdatedBySet;
    }
}
