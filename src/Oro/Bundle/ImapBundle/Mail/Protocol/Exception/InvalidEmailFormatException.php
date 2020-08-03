<?php

namespace Oro\Bundle\ImapBundle\Mail\Protocol\Exception;

use Laminas\Mail\Storage\Exception\RuntimeException;

/**
 * An exception that is thrown when an email format is invalid.
 */
class InvalidEmailFormatException extends RuntimeException
{
}
