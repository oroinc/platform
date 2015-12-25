<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroImapBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Add Access Token field to the oro_email_origin table **/
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('access_token', 'string', ['notnull' => false, 'length' => 255]);
    }
}
