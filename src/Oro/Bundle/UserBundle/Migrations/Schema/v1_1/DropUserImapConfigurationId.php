<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropUserImapConfigurationId implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // copy user's IMAP email origins to new table
        $queries->addPreQuery(
            'INSERT INTO oro_user_email_origin (user_id, origin_id) '
            . 'SELECT id, imap_configuration_id FROM oro_user WHERE imap_configuration_id IS NOT NULL'
        );

        // drop old FK for IMAP email origins
        $table = $schema->getTable('oro_user');
        $table->dropIndex('UNIQ_F82840BC678BF607');
        $table->removeForeignKey('FK_F82840BC678BF607');
        $table->dropColumn('imap_configuration_id');
    }
}
