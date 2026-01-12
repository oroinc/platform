<?php

namespace Oro\Bundle\ScopeBundle\Exception;

/**
 * Exception thrown when a scope criteria value is not supported.
 *
 * This exception is raised when the scope system encounters a criteria value that cannot
 * be processed or is not recognized by the current scope configuration. This typically
 * occurs when attempting to use an unsupported entity type or value in scope criteria
 * resolution.
 */
class NotSupportedCriteriaValueException extends \Exception
{
}
