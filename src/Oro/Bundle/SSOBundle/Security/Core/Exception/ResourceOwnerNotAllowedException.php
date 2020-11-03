<?php

namespace Oro\Bundle\SSOBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * This exception is thrown when a resource owner is not allowed for OAuth single sign-on authentication.
 */
class ResourceOwnerNotAllowedException extends BadCredentialsException
{
}
