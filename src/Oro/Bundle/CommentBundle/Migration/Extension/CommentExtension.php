<?php

namespace Oro\Bundle\CommentBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides an ability to create comment related associations.
 */
class CommentExtension implements ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    const COMMENT_TABLE_NAME = 'oro_comment';

    /**
     * @param Schema      $schema
     * @param string      $targetTableName
     * @param string|null $targetColumnName
     */
    public function addCommentAssociation(Schema $schema, $targetTableName, $targetColumnName = null)
    {
        $commentTable = $schema->getTable(self::COMMENT_TABLE_NAME);
        $targetTable  = $schema->getTable($targetTableName);

        if (empty($targetColumnName)) {
            $primaryKeyColumns = $targetTable->getPrimaryKeyColumns();
            $targetColumnName  = array_shift($primaryKeyColumns);
        }

        $options = new OroOptions();
        $options->set('comment', 'enabled', true);
        $targetTable->addOption(OroOptions::KEY, $options);

        $associationName = ExtendHelper::buildAssociationName(
            $this->extendExtension->getEntityClassByTableName($targetTableName)
        );

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $commentTable,
            $associationName,
            $targetTable,
            $targetColumnName
        );
    }

    /**
     * @param Schema $schema
     * @param string $targetTableName
     *
     * @return bool
     */
    public function hasCommentAssociation(Schema $schema, $targetTableName)
    {
        $commentTable = $schema->getTable(self::COMMENT_TABLE_NAME);
        $targetTable  = $schema->getTable($targetTableName);

        $associationName = ExtendHelper::buildAssociationName(
            $this->extendExtension->getEntityClassByTableName($targetTableName)
        );

        if (!$targetTable->hasPrimaryKey()) {
            throw new SchemaException(
                sprintf('The table "%s" must have a primary key.', $targetTable->getName())
            );
        }
        $primaryKeyColumns = $targetTable->getPrimaryKey()->getColumns();
        if (count($primaryKeyColumns) !== 1) {
            throw new SchemaException(
                sprintf('A primary key of "%s" table must include only one column.', $targetTable->getName())
            );
        }

        $primaryKeyColumnName = array_pop($primaryKeyColumns);

        $nameGenerator = $this->extendExtension->getNameGenerator();
        $selfColumnName = $nameGenerator->generateRelationColumnName(
            $associationName,
            '_' . $primaryKeyColumnName
        );

        return $commentTable->hasColumn($selfColumnName);
    }
}
