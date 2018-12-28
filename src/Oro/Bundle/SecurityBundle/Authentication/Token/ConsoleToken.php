<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Token represent user with organization context for usage by console commands.
 */
class ConsoleToken extends AbstractToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $roles = array())
    {
        parent::__construct($roles);

        parent::setAuthenticated(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return ''; // anonymous credentials
    }
}
