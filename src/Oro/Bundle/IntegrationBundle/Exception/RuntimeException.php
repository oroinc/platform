<?php

namespace Oro\Bundle\IntegrationBundle\Exception;

/**
 * Thrown when an integration operation fails at runtime.
 *
 * This exception is raised when an unexpected error occurs during integration execution,
 * such as network failures, API errors, or other runtime issues that prevent the operation
 * from completing successfully.
 */
class RuntimeException extends \RuntimeException implements IntegrationException
{
}
