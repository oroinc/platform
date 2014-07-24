<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UsernamePasswordOrganizationToken extends UsernamePasswordToken
{
    protected $organizationContext;

    /**
     * @param string $user
     * @param string $credentials
     * @param string $providerKey
     * @param array  $roles
     * @param        $organizationContext
     */
    public function __construct($user, $credentials, $providerKey, array $roles = array(), $organizationContext)
    {
        $this->organizationContext = $organizationContext;
        parent::__construct($user, $credentials, $providerKey, $roles);
    }

    public function getOrganizationContext()
    {
        return $this->organizationContext;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array($this->organizationContext, parent::serialize()));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->organizationContext, $parentStr) = unserialize($serialized);
        parent::unserialize($parentStr);
    }
}
