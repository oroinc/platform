<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class ConsoleToken extends AbstractToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenTrait;

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
