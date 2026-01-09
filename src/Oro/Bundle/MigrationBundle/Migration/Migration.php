<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

/**
 * Defines the contract for database schema migrations.
 *
 * Implementations of this interface represent individual database schema changes that can be
 * applied to the database. Each migration receives a {@see Schema} object for schema modifications
 * and a {@see QueryBag} for executing additional SQL queries before and after schema changes.
 * Migrations are versioned and tracked per bundle to ensure they are applied only once.
 */
interface Migration
{
    /**
     * Modifies the given schema to apply necessary changes of a database
     * The given query bag can be used to apply additional SQL queries before and after schema changes
     *
     * @param Schema   $schema
     * @param QueryBag $queries
     * @return void
     */
    public function up(Schema $schema, QueryBag $queries);
}
