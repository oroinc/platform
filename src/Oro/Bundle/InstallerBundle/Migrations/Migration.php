<?php

namespace Oro\Bundle\InstallerBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

interface Migration
{
    /**
     * @param Schema $schema
     * @return []
     */
    public function up(Schema $schema);
}
