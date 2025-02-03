<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides organization for email templates from token.
 */
class EmailTemplateOrganizationProvider
{
    public function __construct(
        private TokenAccessorInterface $tokenAccessor,
    ) {
    }

    public function getOrganization()
    {
        return $this->tokenAccessor->getOrganization();
    }
}
