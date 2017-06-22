<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class BusinessUnitTreeSearchHandler extends BusinessUnitOwnerSearchHandler
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }
}
