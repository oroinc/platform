<?php

namespace Oro\Bundle\SSOBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken as HWIOAuthToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\AuthenticatedTokenTrait;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenTrait;

/**
 * The OAuth authentication token.
 */
class OAuthToken extends HWIOAuthToken implements OrganizationAwareTokenInterface
{
    use AuthenticatedTokenTrait;
    use OrganizationAwareTokenTrait;
}
