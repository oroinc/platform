<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenSerializerTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * WSSE authentication token.
 */
class WsseToken extends UsernamePasswordToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;
}
