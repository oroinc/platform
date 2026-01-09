<?php

namespace Oro\Bundle\SecurityBundle\Exception;

/**
 * Thrown when an unsupported owner tree provider is encountered.
 *
 * This exception is raised when the system attempts to use an owner tree provider
 * that is not supported or recognized by the current configuration.
 */
class UnsupportedOwnerTreeProviderException extends \RuntimeException
{
}
