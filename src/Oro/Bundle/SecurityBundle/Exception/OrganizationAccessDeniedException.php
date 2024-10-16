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

    #[\Override]
    public function getMessageKey(): string
    {
        return 'oro.security.organization.access_denied';
    }

    #[\Override]
    public function getMessageData(): array
    {
        return ['%organization_name%' => $this->organizationName];
    }

    #[\Override]
    public function __serialize(): array
    {
        return [parent::__serialize(), $this->organizationName];
    }

    #[\Override]
    public function __unserialize(array $data): void
    {
        [$parentData, $this->organizationName] = $data;
        parent::__unserialize($parentData);
    }
}
