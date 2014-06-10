<?php

namespace Oro\Bundle\NoteBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class NoteExtension implements ExtendExtensionAwareInterface
{
    const NOTE_TABLE_NAME = 'oro_note';

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @param Schema $schema
     * @param string $targetTable      Target entity table name
     * @param string $targetColumnName A column name is used to show related entity
     */
    public function addNoteAssociation(
        Schema $schema,
        $targetTable,
        $targetColumnName = null
    ) {
        $noteTable   = $schema->getTable(self::NOTE_TABLE_NAME);
        $entityTable = $schema->getTable($targetTable);

        if (empty($targetColumnName)) {
            $primaryKeyColumns = $entityTable->getPrimaryKeyColumns();
            $targetColumnName  = array_shift($primaryKeyColumns);
        }

        $options = [
            'note' => [
                'enabled' => true
            ]
        ];

        $entityTable->addOption(ExtendColumn::ORO_OPTIONS_NAME, $options);

        $entityAssociationName = ExtendHelper::buildAssociationName(
            $this->extendExtension->getEntityClassByTableName($targetTable)
        );

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $noteTable,
            $entityAssociationName,
            $entityTable,
            $targetColumnName,
            [
                'extend' => [
                    'owner'     => ExtendScope::OWNER_SYSTEM,
                    'is_extend' => true
                ]
            ]
        );
    }
}
