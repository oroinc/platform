<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroImapBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_email_imap');

        $table->dropIndex('UNIQ_17E00D83A832C1C9');
        $table->addIndex(['email_id'], 'IDX_17E00D83A832C1C9');

        $table->addColumn('imap_folder_id', 'integer', ['notnull' => true]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder_imap'),
            ['imap_folder_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null],
            'FK_17E00D834F00B133'
        );
        $table->addIndex(['imap_folder_id'], 'IDX_17E00D834F00B133');
    }
}
