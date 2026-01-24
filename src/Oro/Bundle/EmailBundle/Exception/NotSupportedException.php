<?php

namespace Oro\Bundle\EmailBundle\Exception;

/**
 * Thrown when an unsupported operation or feature is requested.
 *
 * This exception indicates that the requested functionality is not supported by the email bundle
 * or the current configuration, such as unsupported email origin types or operations.
 */
class NotSupportedException extends \RuntimeException
{
}
