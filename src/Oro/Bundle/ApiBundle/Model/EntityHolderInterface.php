<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * Represents a model that is used in API instead of a manageable entity.
 */
interface EntityHolderInterface
{
    /**
     * Gets a manageable entity this model is related to.
     */
    public function getEntity(): ?object;
}
