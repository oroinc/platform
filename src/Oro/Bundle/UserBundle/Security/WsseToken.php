<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenSerializerTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class WsseToken extends UsernamePasswordToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;
}
