<?php

namespace Oro\Bundle\SecurityBundle\Exception;

/**
 * Thrown when an unsupported metadata provider is encountered.
 *
 * This exception is raised when the system attempts to use a metadata provider
 * that is not supported or recognized by the current configuration.
 */
class UnsupportedMetadataProviderException extends \RuntimeException
{
}
