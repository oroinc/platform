<?php

namespace Oro\Bundle\OrganizationBundle\Tools;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

class OwnershipEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var RelationBuilder */
    protected $relationBuilder;

    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    /**
     * @param ConfigManager                      $configManager
     * @param RelationBuilder                    $relationBuilder
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     */
    public function __construct(
        ConfigManager $configManager,
        RelationBuilder $relationBuilder,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
        $this->configManager = $configManager;
        $this->relationBuilder = $relationBuilder;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        return $actionType === ExtendConfigDumper::ACTION_PRE_UPDATE;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $ownershipConfigProvider = $this->configManager->getProvider('ownership');
        $extendConfigProvider    = $this->configManager->getProvider('extend');
        $entityConfigs           = $extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
                continue;
            }
            if (!$ownershipConfigProvider->hasConfig($entityConfig->getId()->getClassName())) {
                continue;
            }
            $ownershipConfig = $ownershipConfigProvider->getConfig($entityConfig->getId()->getClassName());
            $ownerType       = $ownershipConfig->get('owner_type');
            if (empty($ownerType)) {
                continue;
            }

            $this->createOwnerRelation(
                $entityConfig,
                $this->getOwnerTargetEntityClassName($ownerType),
                $ownershipConfig->get('owner_field_name')
            );

            if (in_array($ownerType, [OwnershipType::OWNER_TYPE_USER, OwnershipType::OWNER_TYPE_BUSINESS_UNIT])) {
                if (!$ownershipConfig->has('organization_field_name')) {
                    $ownershipConfig->set('organization_field_name', 'organization');
                    $ownershipConfig->set('organization_column_name', 'organization_id');

                    $this->configManager->persist($ownershipConfig);
                    $organizationFieldName = 'organization';
                } else {
                    $organizationFieldName = $ownershipConfig->get('organization_field_name');
                }

                $this->createOwnerRelation(
                    $entityConfig,
                    $this->ownershipMetadataProvider->getOrganizationClass(),
                    $organizationFieldName
                );
            }
        }
    }

    /**
     * @param ConfigInterface $entityConfig
     * @param string          $targetEntityClassName
     * @param string          $relationName
     */
    protected function createOwnerRelation(ConfigInterface $entityConfig, $targetEntityClassName, $relationName)
    {
        $relationKey = ExtendHelper::buildRelationKey(
            $entityConfig->getId()->getClassName(),
            $relationName,
            'manyToOne',
            $this->ownershipMetadataProvider->getOrganizationClass()
        );
        $relations   = $entityConfig->get('relation', false, []);
        if (!isset($relations[$relationKey])) {
            $this->relationBuilder->addManyToOneRelation(
                $entityConfig,
                $targetEntityClassName,
                $relationName,
                'id',
                [
                    'entity'    => [
                        'label'       => 'oro.custom_entity.' . $relationName . '.label',
                        'description' => 'oro.custom_entity.' . $relationName . '.description',
                    ],
                    'view'      => [
                        'is_displayable' => false
                    ],
                    'form'      => [
                        'is_enabled' => false
                    ],
                    'dataaudit' => [
                        'auditable' => true
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE,
                    ],
                ]
            );
        }
    }

    /**
     * @param string $ownerType
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getOwnerTargetEntityClassName($ownerType)
    {
        switch ($ownerType) {
            case OwnershipType::OWNER_TYPE_USER:
                return $this->ownershipMetadataProvider->getUserClass();
            case OwnershipType::OWNER_TYPE_BUSINESS_UNIT:
                return $this->ownershipMetadataProvider->getBusinessUnitClass();
            case OwnershipType::OWNER_TYPE_ORGANIZATION:
                return $this->ownershipMetadataProvider->getOrganizationClass();
        }

        throw new \InvalidArgumentException(sprintf('Unexpected owner type: %s.', $ownerType));
    }
}
