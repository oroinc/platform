<?php

namespace Oro\Bundle\EntityBundle\EntityProperty;

interface CreatedAtAwareInterface
{
    /**
     * @return \DateTime
     */
    public function getCreatedAt();

    /**
     * @param \DateTime|null $createdAt
     * @return mixed
     */
    public function setCreatedAt(\DateTime $createdAt = null);
}
