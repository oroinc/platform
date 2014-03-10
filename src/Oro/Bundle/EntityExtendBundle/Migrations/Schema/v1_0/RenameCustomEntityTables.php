<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;

class RenameCustomEntityTables implements Migration, RenameExtensionAwareInterface
{
    const OLD_CUSTOM_TABLE_PREFIX = 'oro_extend_';

    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $tables = $schema->getTables();
        foreach ($tables as $table) {
            if (strpos($table->getName(), self::OLD_CUSTOM_TABLE_PREFIX) === 0) {
                $newTableName = ExtendDbIdentifierNameGenerator::
                    . substr($table->getName(), strlen(self::OLD_CUSTOM_TABLE_PREFIX));
                $this->renameExtension->renameTable(
                    $schema,
                    $queries,
                    $table->getName(),
                    $newTableName
                );
            }
        }
    }
}
