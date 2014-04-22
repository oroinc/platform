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
        /** Update table oro_email **/
        $table = $schema->createTable('oro_email');
        $table->addIndex(['message_id'], 'IDX_email_message_id', []);
        /** End of update table oro_email **/
    }
}
