<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_23;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        static::oroEmailTable($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function oroEmailTable(Schema $schema)
    {
        $emailTable = $schema->getTable('oro_email');
        $emailTable->changeColumn('subject', ['length' => 998]);
    }
}
