<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

use Oro\Bundle\SecurityBundle\Migrations\Schema\UpdateOwnershipTypeQuery;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Update all custom entities. Add organization field for entities with ownership type User and Organization
 *
 * Class OrganizationFieldForCustomEntities
 * @package Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_3
 */
class OrganizationFieldForCustomEntities implements Migration, ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        /** @var EntityConfigId[] $entityConfigIds */
        $entityConfigIds = $configManager->getIds('extend');
        $ownerProvider = $configManager->getProvider('ownership');
        foreach ($entityConfigIds as $entityConfigId) {
            if ($configManager->getConfig($entityConfigId)->get('owner') == ExtendScope::OWNER_CUSTOM
                && $ownerProvider->hasConfigById($entityConfigId)
            ) {
                $className   = $entityConfigId->getClassName();
                $ownerConfig = $ownerProvider->getConfig($className);
                if (in_array($ownerConfig->get('owner_type'), ['USER', 'BUSINESS_UNIT'])
                    && !$ownerConfig->has('organization_field_name')
                ) {
                    $tableName    = $configManager->getProvider('extend')
                        ->getConfig($className)
                        ->get('schema')['doctrine'][$className]['table'];
                    $table        = $schema->getTable($tableName);

                    $table->addColumn('organization_id', 'integer', ['notnull' => false]);
                    $table->addIndex(['organization_id']);
                    $table->addForeignKeyConstraint(
                        $schema->getTable('oro_organization'),
                        ['organization_id'],
                        ['id'],
                        ['onDelete' => 'SET NULL', 'onUpdate' => null]
                    );

                    //Add organization fields to ownership entity config
                    $queries->addQuery(
                        new UpdateOwnershipTypeQuery(
                            $entityConfigId->getClassName(),
                            [
                                'organization_field_name'  => 'organization',
                                'organization_column_name' => 'organization_id'
                            ]
                        )
                    );
                }
            }
        }
    }
}
