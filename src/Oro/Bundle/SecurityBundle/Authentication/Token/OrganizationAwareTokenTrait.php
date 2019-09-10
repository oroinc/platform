<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Provides implementation of {@see \Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface}.
 */
trait OrganizationAwareTokenTrait
{
    /** @var Organization|null */
    private $organization;

    /**
     * Gets the organization.
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Sets the organization.
     *
     * @param Organization $organization
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        // clone organization object to have another reference
        // because during deserialization we can have an issue (SegFault on PHP 7.0.11 with ZendOpcache)
        // with restoring same reference twice
        $organization = $this->organization;
        if (null !== $organization) {
            $organization = clone $organization;
        }

        if ($this instanceof AbstractToken) {
            return serialize([$organization, parent::serialize()]);
        }

        return serialize([$organization, '']);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        if (false !== strpos($serialized, '}||a')) {
            // convert from old serialization format
            // this is required because after upgrade to new version of the platform
            // an existing sessions can contain serialized tokens in the old format
            list($serializedOrganization, $serializedTokenWithoutOrganization) = explode('||', $serialized);
            $organization = unserialize($serializedOrganization);
        } else {
            list($organization, $serializedTokenWithoutOrganization) = unserialize($serialized);
        }

        if ($this instanceof AbstractToken) {
            parent::unserialize($serializedTokenWithoutOrganization);
        }
        $this->organization = $organization;
    }
}
