<?php

namespace Oro\Bundle\ImapBundle\Connector\Exception;

/**
 * Thrown when IMAP connector configuration is invalid or incomplete.
 *
 * This exception indicates that the IMAP server configuration parameters
 * (such as host, port, SSL settings, or credentials) are missing or invalid,
 * preventing the connector from establishing a connection to the IMAP server.
 */
class InvalidConfigurationException extends \InvalidArgumentException
{
}
