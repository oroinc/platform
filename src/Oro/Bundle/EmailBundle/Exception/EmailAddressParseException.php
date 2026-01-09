<?php

namespace Oro\Bundle\EmailBundle\Exception;

/**
 * Thrown when an email address cannot be parsed.
 *
 * This exception is raised when attempting to parse an email address string that does not
 * conform to valid email address format or structure.
 */
class EmailAddressParseException extends \RuntimeException
{
}
