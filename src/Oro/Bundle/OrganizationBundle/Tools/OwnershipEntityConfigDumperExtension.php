<?php

namespace Oro\Bundle\OrganizationBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

class OwnershipEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var RelationBuilder */
    protected $relationBuilder;

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /**
     * @param ConfigManager             $configManager
     * @param RelationBuilder           $relationBuilder
     * @param OwnershipMetadataProvider $ownershipMetadataProvider
     */
    public function __construct(
        ConfigManager $configManager,
        RelationBuilder $relationBuilder,
        OwnershipMetadataProvider $ownershipMetadataProvider
    ) {
        $this->configManager             = $configManager;
        $this->relationBuilder           = $relationBuilder;
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
            if (!$entityConfig->is('state', ExtendScope::STATE_NEW)) {
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

            $this->relationBuilder->addManyToOneRelation(
                $entityConfig,
                $this->getOwnerTargetEntity($ownerType),
                $ownershipConfig->get('owner_field_name'),
                'id',
                [
                    'entity'    => [
                        'label'       => 'oro.custom_entity.owner.label',
                        'description' => 'oro.custom_entity.owner.description',
                    ],
                    'view'      => [
                        'is_displayable' => false
                    ],
                    'form'      => [
                        'is_enabled' => false
                    ],
                    'dataaudit' => [
                        'auditable' => true
                    ]
                ]
            );
        }
    }

    /**
     * @param string $ownerType
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getOwnerTargetEntity($ownerType)
    {
        switch ($ownerType) {
            case 'USER':
                return $this->ownershipMetadataProvider->getUserClass();
            case 'BUSINESS_UNIT':
                return $this->ownershipMetadataProvider->getBusinessUnitClass();
            case 'ORGANIZATION':
                return $this->ownershipMetadataProvider->getOrganizationClass();
        }

        throw new \InvalidArgumentException(sprintf('Unexpected owner type: %s.', $ownerType));
    }
}
