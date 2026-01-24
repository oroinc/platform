<?php

namespace Oro\Bundle\SecurityBundle\Exception;

/**
 * Thrown when a required configuration option is missing.
 *
 * This exception is raised when a mandatory configuration option that is required
 * for proper operation is not provided or is not set.
 */
class MissedRequiredOptionException extends \RuntimeException
{
}
