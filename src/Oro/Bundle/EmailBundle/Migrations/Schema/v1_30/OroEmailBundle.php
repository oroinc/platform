<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_30;

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
        self::addEmailUserIndexes($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function addEmailUserIndexes(Schema $schema)
    {
        $table = $schema->getTable('oro_email_user');
        $table->addIndex(
            ['user_owner_id', 'mailbox_owner_id', 'organization_id'],
            'user_owner_id_mailbox_owner_id_organization_id',
            []
        );
    }
}
