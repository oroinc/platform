<?php

namespace Oro\Bundle\InstallerBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroInstallerBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_installer_bundle_version **/
        $table = $schema->createTable('oro_installer_bundle_version');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('bundle_name', 'string', ['length' => 150]);
        $table->addColumn('data_version', 'string', ['notnull' => false, 'length' => 15]);
        $table->addColumn('demo_data_version', 'string', ['notnull' => false, 'length' => 15]);
        $table->setPrimaryKey(['id']);
        /** End of generate table oro_installer_bundle_version **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
