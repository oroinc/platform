<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage\Exception;

use Laminas\Mail\Storage\Exception\RuntimeException;

/**
 * An exception that is thrown when the Imap integration doesn't pass the authentication using OAuth2.
 */
class OAuth2ConnectException extends RuntimeException
{
}
