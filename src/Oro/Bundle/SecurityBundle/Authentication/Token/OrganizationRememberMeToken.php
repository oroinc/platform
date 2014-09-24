<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationRememberMeToken extends RememberMeToken implements OrganizationContextTokenInterface
{
    /** @var Organization */
    protected $organizationContext;

    /**
     * @param UserInterface $user
     * @param string        $providerKey
     * @param string        $key
     * @param Organization $organizationContext
     */
    public function __construct(UserInterface $user, $providerKey, $key, $organizationContext)
    {
        $this->organizationContext = $organizationContext;
        parent::__construct($user, $providerKey, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizationContext()
    {
        return $this->organizationContext;
    }

    /**
     * {@inheritdoc}
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
