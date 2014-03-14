<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension as BaseRenameExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameExtension extends BaseRenameExtension
{
    /**
     * @var ExtendOptionsManager
     */
    protected $extendOptionsManager;

    /**
     * @param ExtendOptionsManager $extendOptionsManager
     */
    public function __construct(ExtendOptionsManager $extendOptionsManager)
    {
        $this->extendOptionsManager = $extendOptionsManager;
    }

    /**
     * {@inheritdoc}
     */
    public function renameColumn(Schema $schema, QueryBag $queries, Table $table, $oldColumnName, $newColumnName)
    {
        $this->extendOptionsManager->setColumnOptions(
            $table->getName(),
            $oldColumnName,
            [
                ExtendOptionsManager::NEW_NAME_OPTION => $newColumnName
            ]
        );
        parent::renameColumn($schema, $queries, $table, $oldColumnName, $newColumnName);
    }
}
