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
    public function getCredentials()
    {
        return ''; // anonymous credentials
    }
}
