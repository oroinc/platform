<?php

namespace Oro\Bundle\SegmentBundle\Model;

/**
 * This interface could be implemented by a segment related query designer classes
 * that are aware about the identifier of a wrapped segment entity.
 */
interface SegmentIdentityAwareInterface
{
    /**
     * Gets the identifier of a segment entity.
     */
    public function getSegmentId(): ?int;
}
