<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage\Exception;

use Laminas\Mail\Storage\Exception\RuntimeException;

/**
 * An exception that is thrown when an email body is empty.
 */
class InvalidBodyFormatException extends RuntimeException
{
}
