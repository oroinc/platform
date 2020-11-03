<?php

namespace Oro\Bundle\SSOBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * This exception is thrown when a user email is not allowed for OAuth single sign-on authentication.
 */
class EmailDomainNotAllowedException extends BadCredentialsException
{
}
