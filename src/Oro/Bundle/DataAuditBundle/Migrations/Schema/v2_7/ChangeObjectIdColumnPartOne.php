<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_7;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeObjectIdColumnPartOne implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_audit');

        if ($table->hasColumn('entity_id')) {
            $queries->addPreQuery('UPDATE oro_audit SET entity_id = object_id WHERE entity_id IS NULL');

            $table->dropColumn('object_id');
            $table->dropIndex('idx_oro_audit_version');
            $table->dropIndex('idx_oro_audit_ent_by_type');

            $table->addColumn('object_id', 'string', ['length' => 255, 'notnull' => false]);
            $table->addUniqueIndex(['object_id', 'object_class', 'version'], 'idx_oro_audit_version');
        } else {
            $table->getColumn('object_id')
                ->setType(Type::getType(Types::STRING))
                ->setOptions(['notnull' => false, 'length' => 255]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 10;
    }
}
