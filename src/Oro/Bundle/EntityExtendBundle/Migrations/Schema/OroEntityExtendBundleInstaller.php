<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_0\RenameExtendTablesAndColumns;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEntityExtendBundleInstaller extends RenameExtendTablesAndColumns implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_13';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // rename should not be performed during a fresh installation
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            parent::up($schema, $queries);
        }

        self::oroEnumValueTransTable($schema);
    }

    /**
     * Generate table oro_enum_value_trans
     */
    public static function oroEnumValueTransTable(Schema $schema)
    {
        /** Generate table oro_enum_value_trans **/
        $table = $schema->createTable('oro_enum_value_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 32]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 16]);
        $table->addColumn('object_class', 'string', ['length' => 191]);
        $table->addColumn('field', 'string', ['length' => 4]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'oro_enum_value_trans_idx', []);
        /** End of generate table oro_enum_value_trans **/
    }
}
