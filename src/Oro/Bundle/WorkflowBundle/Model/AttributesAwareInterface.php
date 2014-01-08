<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;

interface AttributesAwareInterface
{
    /**
     * Set attributes collection.
     *
     * @param Collection $attributes
     * @return AttributesAwareInterface
     */
    public function setAttributes(Collection $attributes);
}
