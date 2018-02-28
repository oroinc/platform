<?php

namespace Oro\Bundle\SSOBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken as HWIOAuthToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenSerializerTrait;

class OAuthToken extends HWIOAuthToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;
}
