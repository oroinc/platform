<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;

class AddLifetimeColumnToSessions implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_session');
        $table->addColumn('sess_lifetime', 'integer', ['nullable' => true]);
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_session SET sess_lifetime = :lifetime',
                ['lifetime' => 3600],
                ['lifetime' => TYPE::INTEGER]
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
