<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Symfony\Component\Security\Guard\Token\GuardTokenInterface;

/**
 * Extends UsernamePasswordOrganizationToken to add ability to use it in guard authentication.
 */
class ImpersonationUsernamePasswordOrganizationToken extends UsernamePasswordOrganizationToken implements
    GuardTokenInterface,
    ImpersonationTokenInterface
{
}
