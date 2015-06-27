<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddStatusField implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addChangedStatusAt($schema);
        self::addPostQueries($queries);
    }

    /**
     * @param Schema $schema
     */
    public static function addChangedStatusAt(Schema $schema)
    {
        $emailUserTable = $schema->getTable('oro_email_user');
        $emailUserTable->addColumn('changed_status_at', 'datetime');
    }

    /**
     * @param QueryBag $queries
     */
    public static function addPostQueries(QueryBag $queries)
    {
        $queries->addPostQuery(new FillEmailUserStatusAtQuery());
    }
}
