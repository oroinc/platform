<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenSerializerTrait;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class WsseToken extends AbstractToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return '';
    }
}
