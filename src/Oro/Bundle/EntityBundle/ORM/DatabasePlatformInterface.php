<?php

namespace Oro\Bundle\EntityBundle\ORM;

/**
 * Defines database platform constants.
 *
 * This interface provides constants for identifying different database platforms
 * (PostgreSQL and MySQL) used in the application.
 */
interface DatabasePlatformInterface
{
    const DATABASE_POSTGRESQL = 'postgresql';
    const DATABASE_MYSQL      = 'mysql';
}
