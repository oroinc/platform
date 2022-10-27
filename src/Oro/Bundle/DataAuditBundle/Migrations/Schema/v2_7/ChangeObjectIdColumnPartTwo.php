<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeObjectIdColumnPartTwo implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_audit');

        if ($table->hasColumn('entity_id')) {
            $queries->addPreQuery('UPDATE oro_audit SET object_id = entity_id WHERE object_id IS NULL');

            $table->dropColumn('entity_id');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 20;
    }
}
