<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_21;

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
        static::addIndexes($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function addIndexes(Schema $schema)
    {
        $emailRecipientTable = $schema->getTable('oro_email_recipient');
        $emailRecipientTable->addIndex(['email_id', 'type'], 'email_id_type_idx', []);

        $emailOriginTable = $schema->getTable('oro_email_origin');
        $emailOriginTable->addIndex(['isActive', 'name'], 'isActive_name_idx', []);

        $emailTable = $schema->getTable('oro_email');
        $emailTable->addIndex(['sent'], 'IDX_sent', []);
    }
}
