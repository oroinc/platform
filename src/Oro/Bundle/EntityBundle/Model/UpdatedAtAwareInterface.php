<?php

namespace Oro\Bundle\EntityBundle\Model;

interface UpdatedAtAwareInterface
{
    /**
     * @return \DateTime
     */
    public function getUpdatedAt();

    /**
     * @param \DateTime $updatedAt
     * @return mixed
     */
    public function setUpdatedAt(\DateTime $updatedAt = null);

    /**
     * @return bool
     */
    public function isUpdatedAtSetted();
}
