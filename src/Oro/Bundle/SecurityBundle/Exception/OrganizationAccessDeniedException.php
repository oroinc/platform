<?php

namespace Oro\Bundle\SecurityBundle\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class OrganizationAccessDeniedException extends AuthenticationException
{
    /**
     * @var string
     */
    protected $organizationName;

    /**
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

    public function serialize()
    {
        return serialize([
            $this->getToken(),
            $this->code,
            $this->message,
            $this->file,
            $this->line,
            $this->organizationName,
        ]);
    }

    public function unserialize($str)
    {
        list(
            $token,
            $this->code,
            $this->message,
            $this->file,
            $this->line,
            $this->organizationName
        ) = unserialize($str);

        $this->setToken($token);
    }
}
