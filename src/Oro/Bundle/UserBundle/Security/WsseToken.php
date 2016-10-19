<?php

namespace Oro\Bundle\UserBundle\Security;

use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Token\Token;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenSerializerTrait;

class WsseToken extends Token implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;
}
