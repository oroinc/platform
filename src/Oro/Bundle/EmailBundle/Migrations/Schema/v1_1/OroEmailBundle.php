<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(
            "UPDATE oro_email SET message_id = CONCAT('id.', REPLACE(UUID(), '-',''),'@swift.generated') " .
            "WHERE message_id IS NULL"
        );

        /** Update table oro_email **/
        $table = $schema->getTable('oro_email');
        $table->changeColumn('message_id', ['notnull' => true]);
        $table->addIndex(['message_id'], 'IDX_email_message_id', []);
        /** End of update table oro_email **/
    }
}
