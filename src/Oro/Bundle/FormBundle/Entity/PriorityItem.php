<?php

namespace Oro\Bundle\FormBundle\Entity;

interface PriorityItem
{
    /**
     * Get item priority
     *
     * @return int
     */
    public function getPriority();

    /**
     * Set item priority
     *
     * @param int $priority
     */
    public function setPriority($priority);
}
