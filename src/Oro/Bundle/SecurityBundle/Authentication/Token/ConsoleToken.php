<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Token represent user with organization context for usage by console commands.
 */
class ConsoleToken extends AbstractToken implements OrganizationAwareTokenInterface, RolesAwareTokenInterface
{
    use RolesAndOrganizationAwareTokenTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $roles = [])
    {
        parent::__construct($this->initRoles($roles));

        $this->setAuthenticated(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return ''; // anonymous credentials
    }
}
