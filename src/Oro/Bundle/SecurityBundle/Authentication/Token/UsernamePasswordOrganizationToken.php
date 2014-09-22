<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UsernamePasswordOrganizationToken extends UsernamePasswordToken implements OrganizationContextTokenInterface
{
    /** @var Organization */
    protected $organizationContext;

    /** @var OrganizationManager */
    protected $manager;

    /**
     * @param string       $user
     * @param string       $credentials
     * @param string       $providerKey
     * @param array        $roles
     * @param Organization $organizationContext
     */
    public function __construct($user, $credentials, $providerKey, Organization $organizationContext, array $roles = [])
    {
        $this->organizationContext = $organizationContext;
        parent::__construct($user, $credentials, $providerKey, $roles);
    }

    /**
     * @return Organization
     */
    public function getOrganizationContext()
    {
        return $this->organizationContext;
    }

    /**
     * @param Organization $organization
     */
    public function setOrganizationContext(Organization $organization)
    {
        $this->organizationContext = $organization;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $objectSerialize = serialize($this->organizationContext);
        $string = implode('||', array($objectSerialize, parent::serialize()));
        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($organizationData, $parentStr) = explode('||', $serialized);
        $this->organizationContext = unserialize($organizationData);
        parent::unserialize($parentStr);
    }
}
