<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateConstraints implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::changeConstraintDecimalFk($schema);
        self::changeConstraintIntegerFk($schema);
        self::changeConstraintDatetimeFk($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function changeConstraintDecimalFk(Schema $schema)
    {
        $table = $schema->getTable('oro_search_index_decimal');

        if ($table->getForeignKey('FK_E0B9BB33126F525E')) {
            $table->removeForeignKey('FK_E0B9BB33126F525E');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_search_item'),
                ['item_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function changeConstraintIntegerFk(Schema $schema)
    {
        $table = $schema->getTable('oro_search_index_integer');
        if ($table->getForeignKey('FK_E04BA3AB126F525E')) {
            $table->removeForeignKey('FK_E04BA3AB126F525E');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_search_item'),
                ['item_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function changeConstraintDatetimeFk(Schema $schema)
    {
        $table = $schema->getTable('oro_search_index_datetime');
        if ($table->getForeignKey('FK_459F212A126F525E')) {
            $table->removeForeignKey('FK_459F212A126F525E');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_search_item'),
                ['item_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }
    }
}
