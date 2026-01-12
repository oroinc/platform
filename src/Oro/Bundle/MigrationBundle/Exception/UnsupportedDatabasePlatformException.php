<?php

namespace Oro\Bundle\MigrationBundle\Exception;

/**
 * Thrown when a migration or operation is attempted on an unsupported database platform.
 *
 * This exception indicates that the current database platform (e.g., PostgreSQL, MySQL, SQLite)
 * is not supported for the requested migration or operation.
 */
class UnsupportedDatabasePlatformException extends \Exception
{
}
