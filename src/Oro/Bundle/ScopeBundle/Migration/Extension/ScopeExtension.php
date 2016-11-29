<?php

namespace Oro\Bundle\ScopeBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\ScopeBundle\Migrations\Schema\OroScopeBundleInstaller;

class ScopeExtension implements ExtendExtensionAwareInterface
{
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
     * Adds the association between the target table and the scope table
     *
     * @param Schema $schema
     * @param string $scopeAssociationName
     * @param string $targetTableName Target entity table name
     * @param string $targetAssociationName
     */
    public function addScopeAssociation(Schema $schema, $scopeAssociationName, $targetTableName, $targetAssociationName)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            OroScopeBundleInstaller::ORO_SCOPE,
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
