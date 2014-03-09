<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;

class RenameCustomEntities extends Migration implements RenameExtensionAwareInterface
{
    const OLD_TABLE_PREFIX = 'oro_extend_';

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
            if (strpos($table->getName(), self::OLD_TABLE_PREFIX) === 0) {
                $newTableName = ExtendConfigDumper::TABLE_PREFIX
                    . substr($table->getName(), strlen(self::OLD_TABLE_PREFIX));
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
