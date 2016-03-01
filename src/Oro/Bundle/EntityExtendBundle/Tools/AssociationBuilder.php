<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\Common\Persistence\Mapping\MappingException as PersistenceMappingException;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class AssociationBuilder
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ConfigManager */
    protected $configManager;

    /** @var RelationBuilder */
    protected $relationBuilder;

    /**
     * @param ManagerRegistry $doctrine
     * @param ConfigManager   $configManager
     * @param RelationBuilder $relationBuilder
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        RelationBuilder $relationBuilder
    ) {
        $this->doctrine        = $doctrine;
        $this->configManager   = $configManager;
        $this->relationBuilder = $relationBuilder;
    }

    /**
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     * @param string $associationKind
     */
    public function createManyToManyAssociation($sourceEntityClass, $targetEntityClass, $associationKind)
    {
        $relationName = ExtendHelper::buildAssociationName($targetEntityClass, $associationKind);

        $extendConfigProvider = $this->configManager->getProvider('extend');

        $targetEntityPrimaryKeyColumns = $this->getPrimaryKeyColumnNames($targetEntityClass);

        // add relation to owning entity
        $this->relationBuilder->addManyToManyRelation(
            $extendConfigProvider->getConfig($sourceEntityClass),
            $targetEntityClass,
            $relationName,
            $targetEntityPrimaryKeyColumns,
            $targetEntityPrimaryKeyColumns,
            $targetEntityPrimaryKeyColumns
        );

        // update attributes for new association
        $fieldConfig = $extendConfigProvider->getConfig($sourceEntityClass, $relationName);
        if ($fieldConfig->is('state', ExtendScope::STATE_NEW)) {
            $targetEntityConfig = $this->configManager->getProvider('entity')->getConfig($targetEntityClass);
            $this->relationBuilder->updateFieldConfigs(
                $sourceEntityClass,
                $relationName,
                [
                    'extend' => [
                        'without_default' => true,
                    ],
                    'entity' => [
                        'label'       => $this->getAssociationLabel(
                            'plural_label',
                            $sourceEntityClass,
                            $relationName,
                            $targetEntityConfig
                        ),
                        'description' => $this->getAssociationLabel(
                            'description',
                            $sourceEntityClass,
                            $relationName,
                            $targetEntityConfig
                        ),
                    ],
                    'view'   => [
                        'is_displayable' => true
                    ],
                    'form'   => [
                        'is_enabled' => true
                    ]
                ]
            );
        }
    }

    /**
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     * @param string $associationKind
     */
    public function createManyToOneAssociation($sourceEntityClass, $targetEntityClass, $associationKind)
    {
        $relationName = ExtendHelper::buildAssociationName($targetEntityClass, $associationKind);

        $extendConfigProvider = $this->configManager->getProvider('extend');

        $targetEntityPrimaryKeyColumns = $this->getPrimaryKeyColumnNames($targetEntityClass);
        $targetFieldName               = reset($targetEntityPrimaryKeyColumns);

        // add relation to owning entity
        $this->relationBuilder->addManyToOneRelation(
            $extendConfigProvider->getConfig($sourceEntityClass),
            $targetEntityClass,
            $relationName,
            $targetFieldName
        );

        // update attributes for new association
        $fieldConfig = $extendConfigProvider->getConfig($sourceEntityClass, $relationName);
        if ($fieldConfig->is('state', ExtendScope::STATE_NEW)) {
            $targetEntityConfig = $this->configManager->getProvider('entity')->getConfig($targetEntityClass);
            $this->relationBuilder->updateFieldConfigs(
                $sourceEntityClass,
                $relationName,
                [
                    'entity' => [
                        'label'       => $this->getAssociationLabel(
                            'label',
                            $sourceEntityClass,
                            $relationName,
                            $targetEntityConfig
                        ),
                        'description' => $this->getAssociationLabel(
                            'description',
                            $sourceEntityClass,
                            $relationName,
                            $targetEntityConfig
                        ),
                    ],
                    'view'   => [
                        'is_displayable' => false
                    ],
                    'form'   => [
                        'is_enabled' => false
                    ]
                ]
            );
        }
    }

    /**
     * @param string $entityClass
     *
     * @return string[]
     */
    protected function getPrimaryKeyColumnNames($entityClass)
    {
        try {
            /** @var EntityManager $em */
            $em = $this->doctrine->getManagerForClass($entityClass);

            return $em->getClassMetadata($entityClass)->getIdentifierColumnNames();
        } catch (\ReflectionException $e) {
            // ignore entity not found exception
            return ['id'];
        } catch (ORMMappingException $e) {
            // ignore any doctrine mapping exceptions
            // it may happens if the entity has relation to deleted custom entity
            // or during update schema for newly created custom entity with relation
            return ['id'];
        } catch (PersistenceMappingException $e) {
            return ['id'];
        }
    }

    /**
     * @param string          $labelKey
     * @param string          $entityClass
     * @param string          $relationName
     * @param ConfigInterface $targetEntityConfig
     *
     * @return string
     */
    protected function getAssociationLabel($labelKey, $entityClass, $relationName, ConfigInterface $targetEntityConfig)
    {
        $label = $targetEntityConfig->get($labelKey);
        if (!$label) {
            $label = ConfigHelper::getTranslationKey(
                'entity',
                $labelKey,
                $entityClass,
                $relationName
            );
        }

        return $label;
    }
}
