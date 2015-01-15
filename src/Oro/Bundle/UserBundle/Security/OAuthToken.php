<?php

namespace Oro\Bundle\UserBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken as HWIOAuthToken;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenTrait;

class OAuthToken extends HWIOAuthToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenTrait;

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize([
            $this->organization,
            parent::serialize()
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        list($this->organization, $parent) = $data;
        parent::unserialize($parent);
    }
}
