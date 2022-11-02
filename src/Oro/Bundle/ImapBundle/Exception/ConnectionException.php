<?php

namespace Oro\Bundle\ImapBundle\Exception;

use Laminas\Mail\Protocol\Exception\RuntimeException;

/**
 * An exception that throws if connection to the IMAP server was failed.
 */
class ConnectionException extends RuntimeException
{
}
