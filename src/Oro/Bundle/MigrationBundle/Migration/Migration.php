<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

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
