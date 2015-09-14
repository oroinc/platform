<?php

namespace Oro\Bundle\EntityBundle\Model;

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
    protected $isUpdatedBySetted;

    /**
     * @param User|null $updatedBy
     *
     * @return $this
     */
    public function setUpdatedBy(User $updatedBy = null)
    {
        $this->isUpdatedBySetted = false;
        if ($updatedBy !== null) {
            $this->isUpdatedBySetted = true;
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
    public function isUpdatedBySetted()
    {
        return $this->isUpdatedBySetted;
    }
}
