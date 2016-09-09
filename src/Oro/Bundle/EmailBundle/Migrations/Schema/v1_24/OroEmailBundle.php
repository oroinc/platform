<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_24;

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
        static::removeIndex($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function removeIndex(Schema $schema)
    {
        $emailRecipientTable = $schema->getTable('oro_email_recipient');
        $emailRecipientTable->dropIndex('IDX_7DAF9656A832C1C9');
    }
}
