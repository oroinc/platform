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
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
    }

    /**
     * {@inheritdoc}
     */
    public function __serialize(): array
    {
        // clone organization object to have another reference
        // because during deserialization we can have an issue (SegFault on PHP 7.0.11 with ZendOpcache)
        // with restoring same reference twice
        $organization = $this->organization;
        if (null !== $organization) {
            $organization = clone $organization;
        }

        if ($this instanceof AbstractToken) {
            return [$organization, parent::__serialize()];
        }

        return [$organization, ''];
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize(array $serialized): void
    {
        list($organization, $serializedTokenWithoutOrganization) = $serialized;

        if ($this instanceof AbstractToken) {
            parent::__unserialize($serializedTokenWithoutOrganization);
        }
        $this->organization = $organization;
    }
}
