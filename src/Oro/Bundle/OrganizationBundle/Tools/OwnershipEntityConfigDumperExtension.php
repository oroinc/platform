<?php

namespace Oro\Bundle\OrganizationBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
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

    /** @var ConfigInterface[] */
    private $targetEntityConfigs;

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
        if ($actionType === ExtendConfigDumper::ACTION_PRE_UPDATE) {
            $targetEntityConfigs = $this->getTargetEntityConfigs();

            return !empty($targetEntityConfigs);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(array &$extendConfigs)
    {
        $targetEntityConfigs = $this->getTargetEntityConfigs();
        foreach ($targetEntityConfigs as $targetEntityConfig) {
            $this->createOwnerRelation(
                $this->getOwnerTargetEntity($targetEntityConfig->get('owner_type')),
                $targetEntityConfig->getId()->getClassName(),
                $targetEntityConfig->get('owner_field_name')
            );
        }
    }

    /**
     * Gets the list of configs for entities which can be the target of the association
     *
     * @return ConfigInterface[]
     */
    protected function getTargetEntityConfigs()
    {
        if (null === $this->targetEntityConfigs) {
            $this->targetEntityConfigs = [];

            $ownershipConfigs     = $this->configManager->getProvider('ownership')->getConfigs();
            foreach ($ownershipConfigs as $ownershipConfig) {
                $ownerType = $ownershipConfig->get('owner_type');
                if (!empty($ownerType) && $this->isCustomEntity($ownershipConfig->getId()->getClassName())) {
                    $this->targetEntityConfigs[] = $ownershipConfig;
                }
            }
        }

        return $this->targetEntityConfigs;
    }

    /**
     * @param string $className
     * @return bool
     */
    protected function isCustomEntity($className)
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        if ($extendConfigProvider->hasConfig($className)) {
            $extendConfig = $extendConfigProvider->getConfig($className);
            if ($extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
                return true;
            }
        }

        return false;
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

    /**
     * @param string $targetEntityClass
     * @param string $sourceEntityClass
     * @param string $relationName
     */
    protected function createOwnerRelation($targetEntityClass, $sourceEntityClass, $relationName)
    {
        $relationKey = ExtendHelper::buildRelationKey(
            $sourceEntityClass,
            $relationName,
            'manyToOne',
            $targetEntityClass
        );

        // create field
        $this->relationBuilder->addFieldConfig(
            $sourceEntityClass,
            $relationName,
            'manyToOne',
            [
                'extend'    => [
                    'owner'         => ExtendScope::OWNER_SYSTEM,
                    'state'         => ExtendScope::STATE_NEW,
                    'extend'        => true,
                    'target_entity' => $targetEntityClass,
                    'target_field'  => 'id',
                    'relation_key'  => $relationKey,
                ],
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

        // add relation to owning entity
        $this->relationBuilder->addManyToOneRelation(
            $targetEntityClass,
            $sourceEntityClass,
            $relationName,
            $relationKey
        );
    }
}
