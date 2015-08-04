<?php
namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddLifetimeColumnToSessions implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_session');
        $table->addColumn('sess_lifetime', 'integer', ['nullable' => false]);
    }
}
