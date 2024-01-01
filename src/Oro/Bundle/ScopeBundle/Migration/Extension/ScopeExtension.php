<?php

namespace Oro\Bundle\ScopeBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;

/**
 * Provides an ability to create scope related associations.
 */
class ScopeExtension implements ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    /**
     * Adds the association between the target table and the scope table.
     */
    public function addScopeAssociation(
        Schema $schema,
        string $scopeAssociationName,
        string $targetTableName,
        string $targetAssociationName
    ): void {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_scope',
            $scopeAssociationName,
            $targetTableName,
            $targetAssociationName,
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                    'on_delete' => 'CASCADE',
                    'nullable' => true,
                ],
                'datagrid' => [
                    'is_visible' => DatagridScope::IS_VISIBLE_FALSE,
                ],
            ]
        );
    }
}
