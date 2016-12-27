<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration
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
        if (!$table->hasIndex('user_owner_id_mailbox_owner_id_organization_id')) {
            $table->addIndex(
                ['user_owner_id', 'mailbox_owner_id', 'organization_id'],
                'user_owner_id_mailbox_owner_id_organization_id',
                []
            );
        }
    }
}
