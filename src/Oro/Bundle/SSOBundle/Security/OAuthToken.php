<?php

namespace Oro\Bundle\SSOBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken as HWIOAuthToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\RolesAndOrganizationAwareTokenTrait;
use Oro\Bundle\SecurityBundle\Authentication\Token\RolesAwareTokenInterface;

/**
 * The OAuth authentication token.
 */
class OAuthToken extends HWIOAuthToken implements OrganizationAwareTokenInterface, RolesAwareTokenInterface
{
    use RolesAndOrganizationAwareTokenTrait;

    public function __construct($accessToken, array $roles = [])
    {
        parent::__construct($accessToken, $this->initRoles($roles));
    }
}
