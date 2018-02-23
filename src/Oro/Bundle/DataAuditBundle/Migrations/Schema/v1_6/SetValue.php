<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SetValue implements Migration, OrderedMigrationInterface
{
    /** {@inheritdoc} */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_audit SET type = :type',
                ['type' => 'audit'],
                ['type' => Type::STRING]
            )
        );
    }
}
