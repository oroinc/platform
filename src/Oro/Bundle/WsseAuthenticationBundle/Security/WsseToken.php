<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The WSSE authentication token.
 */
class WsseToken extends UsernamePasswordToken implements OrganizationAwareTokenInterface
{
    use OrganizationAwareTokenTrait;
}
