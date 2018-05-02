<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenSerializerTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken as Token;

class WsseToken extends Token implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;
}
