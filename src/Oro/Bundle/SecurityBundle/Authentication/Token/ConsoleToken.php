<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Token represent user with organization context for usage by console commands.
 */
class ConsoleToken extends AbstractToken implements OrganizationAwareTokenInterface
{
    use AuthenticatedTokenTrait;
    use OrganizationAwareTokenTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $roles = [])
    {
        parent::__construct($roles);

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
