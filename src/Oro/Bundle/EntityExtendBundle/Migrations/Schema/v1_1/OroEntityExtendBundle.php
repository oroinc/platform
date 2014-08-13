<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEntityExtendBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::oroEnumValueTransTable($schema);
    }

    /**
     * Generate table oro_enum_value_trans
     *
     * @param Schema $schema
     */
    public static function oroEnumValueTransTable(Schema $schema)
    {
        /** Generate table oro_enum_value_trans **/
        $table = $schema->createTable('oro_enum_value_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 32]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'oro_enum_value_trans_idx', []);
        /** End of generate table oro_enum_value_trans **/

    }
}
