<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

interface Migration
{
    /**
     * @param Schema $schema
     * @return []
     */
    public function up(Schema $schema);
}
