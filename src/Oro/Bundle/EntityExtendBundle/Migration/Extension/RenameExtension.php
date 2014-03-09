<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension as BaseRenameExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameExtension extends BaseRenameExtension
{
    /**
     * {@inheritdoc}
     */
    public function renameTable(Schema $schema, QueryBag $queries, $oldTableName, $newTableName)
    {
        $table = $schema->getTable($oldTableName);
        $table->addOption('oro_options', ['extend' => ['table' => $newTableName]]);

        parent::renameTable($schema, $queries, $oldTableName, $newTableName);
    }
}
