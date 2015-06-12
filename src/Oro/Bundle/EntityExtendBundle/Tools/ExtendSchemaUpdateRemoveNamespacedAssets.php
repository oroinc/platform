<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Visitor\AbstractVisitor;

/**
 * This class is a copy of "Doctrine\DBAL\Schema\Visitor\RemoveNamespacedAssets" and it is used in
 * the command "oro:entity-extend:update-schema" to prevent removing foreign keys between
 * extended and regular entities (for details see method "acceptForeignKey").
 *
 * Unfortunately due a poor design of the Doctrine\ORM\Tools\SchemaTool::getSchemaFromMetadata
 * we have to use "class_alias" to replace "Doctrine\DBAL\Schema\Visitor\RemoveNamespacedAssets"
 * with "Oro\Bundle\EntityExtendBundle\Tools\ExtendSchemaUpdateRemoveNamespacedAssets".
 * And by the same reason we cannot extend our class from "Doctrine\DBAL\Schema\Visitor\RemoveNamespacedAssets".
 */
class ExtendSchemaUpdateRemoveNamespacedAssets extends AbstractVisitor
{
    /**
     * @var Schema
     */
    private $schema;

    /**
     * {@inheritdoc}
     */
    public function acceptSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptTable(Table $table)
    {
        if (!$table->isInDefaultNamespace($this->schema->getName())) {
            $this->schema->dropTable($table->getName());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
        if (!$sequence->isInDefaultNamespace($this->schema->getName())) {
            $this->schema->dropSequence($sequence->getName());
        }
    }


    /**
     * {@inheritdoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        // do nothing here
        // we do not need to remove any foreign keys because extended entities can reference to regular entities
    }
}
