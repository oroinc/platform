<?php

namespace Oro\Bundle\SecurityBundle\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * This exception is thrown if the organization is locked.
 */
class OrganizationAccessDeniedException extends AuthenticationException
{
    /** @var string */
    private $organizationName;

    /**
     * Gets the organization name.
     *
     * @return string
     */
    public function getOrganizationName()
    {
        return $this->organizationName;
    }

    /**
     * Sets the organization name.
     *
     * @param string $organizationName
     */
    public function setOrganizationName($organizationName)
    {
        $this->organizationName = $organizationName;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'oro.security.organization.access_denied';
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageData()
    {
        return ['%organization_name%' => $this->organizationName];
    }

    public function __serialize(): array
    {
        return [parent::__serialize(), $this->organizationName];
    }

    public function __unserialize(array $data): void
    {
        [$parentData, $this->organizationName] = $data;
        parent::__unserialize($parentData);
    }
}
