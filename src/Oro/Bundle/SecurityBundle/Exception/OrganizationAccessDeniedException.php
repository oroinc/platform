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

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([parent::serialize(), $this->organizationName]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        list($parentData, $this->organizationName) = unserialize($str);
        parent::unserialize($parentData);
    }
}
