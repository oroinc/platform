<?php

namespace Oro\Bundle\SecurityBundle\Exception;

/**
 * Thrown when no suitable ownership decision maker can be found.
 *
 * This exception is raised when the system cannot locate an ownership decision maker
 * that supports the current security context or entity type.
 */
class NotFoundSupportedOwnershipDecisionMakerException extends \RuntimeException
{
}
