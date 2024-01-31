<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

/**
 * Extends UsernamePasswordOrganizationToken to add ability to use it in authentication.
 */
class ImpersonationUsernamePasswordOrganizationToken extends UsernamePasswordOrganizationToken implements
    ImpersonationTokenInterface
{
}
