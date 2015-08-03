<?php
namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddLifetimeColumnToSessions implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $t = $schema->getTable('oro_session');
        $t->addColumn('sess_lifetime', 'integer', ['nullable' => false]);
    }
}
