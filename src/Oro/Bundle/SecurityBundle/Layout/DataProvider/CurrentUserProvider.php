<?php

namespace Oro\Bundle\SecurityBundle\Layout\DataProvider;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class CurrentUserProvider
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @return object|null
     */
    public function getCurrentUser()
    {
        return $this->tokenAccessor->getUser();
    }
}
