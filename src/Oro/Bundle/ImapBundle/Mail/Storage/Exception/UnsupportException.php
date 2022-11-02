<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage\Exception;

use Laminas\Mail\Storage\Exception\RuntimeException;

/**
 * An exception that is thrown when the Imap server does not support UID SEARCH.
 */
class UnsupportException extends RuntimeException
{
}
