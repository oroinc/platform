<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Base autocomplete search handler for business unit tree structures.
 *
 * Includes a setter for {@see TokenAccessorInterface}.
 */
class BusinessUnitTreeSearchHandler extends BusinessUnitOwnerSearchHandler
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }
}
