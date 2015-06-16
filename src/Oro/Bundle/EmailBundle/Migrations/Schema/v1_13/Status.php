<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class Status implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addChangeStatusAt($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function addChangeStatusAt(Schema $schema)
    {
        $emailUserTable = $schema->getTable('oro_email_user');
        $emailUserTable->addColumn('change_status_at', 'datetime');
    }
}
