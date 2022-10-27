<?php

namespace Oro\Bundle\SecurityBundle\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * This exception is thrown when an user's organization is not valid
 * or an user has no valid organizations during the authentication.
 */
class BadUserOrganizationException extends AuthenticationException
{
}
