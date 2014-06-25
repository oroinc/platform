<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\ORM\Mapping\MappingException;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class AssociationBuilder
{
    /** @var RelationBuilder */
    protected $relationBuilder;

    /**
     * @param RelationBuilder $relationBuilder
     */
    public function __construct(RelationBuilder $relationBuilder)
    {
        $this->relationBuilder = $relationBuilder;
    }

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->relationBuilder->getConfigManager();
    }

    /**
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     * @param string $associationKind
     */
    public function createManyToManyAssociation($sourceEntityClass, $targetEntityClass, $associationKind)
    {
        $relationName = ExtendHelper::buildAssociationName($targetEntityClass, $associationKind);
        $relationKey  = ExtendHelper::buildRelationKey(
            $sourceEntityClass,
            $relationName,
            'manyToMany',
            $targetEntityClass
        );

        $entityConfigProvider = $this->getConfigManager()->getProvider('entity');
        $targetEntityConfig   = $entityConfigProvider->getConfig($targetEntityClass);

        $label       = $targetEntityConfig->get(
            'label',
            false,
            ConfigHelper::getTranslationKey('entity', 'label', $targetEntityClass, $relationName)
        );
        $description = ConfigHelper::getTranslationKey('entity', 'description', $targetEntityClass, $relationName);

        $targetEntityPrimaryKeyColumns = $this->getPrimaryKeyColumnNames($targetEntityClass);

        // create field
        $this->relationBuilder->addFieldConfig(
            $sourceEntityClass,
            $relationName,
            'manyToMany',
            [
                'extend' => [
                    'owner'           => ExtendScope::OWNER_SYSTEM,
                    'state'           => ExtendScope::STATE_NEW,
                    'extend'          => true,
                    'without_default' => true,
                    'relation_key'    => $relationKey,
                    'target_entity'   => $targetEntityClass,
                    'target_grid'     => $targetEntityPrimaryKeyColumns,
                    'target_title'    => $targetEntityPrimaryKeyColumns,
                    'target_detailed' => $targetEntityPrimaryKeyColumns,
                ],
                'entity' => [
                    'label'       => $label,
                    'description' => $description,
                ],
                'view'   => [
                    'is_displayable' => true
                ],
                'form'   => [
                    'is_enabled' => true
                ]
            ]
        );

        // add relation to owning entity
        $this->relationBuilder->addManyToManyRelation(
            $targetEntityClass,
            $sourceEntityClass,
            $relationName,
            $relationKey
        );
    }

    /**
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     * @param string $associationKind
     */
    public function createManyToOneAssociation($sourceEntityClass, $targetEntityClass, $associationKind)
    {
        $relationName = ExtendHelper::buildAssociationName($targetEntityClass, $associationKind);
        $relationKey  = ExtendHelper::buildRelationKey(
            $sourceEntityClass,
            $relationName,
            'manyToOne',
            $targetEntityClass
        );

        $entityConfigProvider = $this->getConfigManager()->getProvider('entity');
        $targetEntityConfig   = $entityConfigProvider->getConfig($targetEntityClass);

        $label       = $targetEntityConfig->get(
            'label',
            false,
            ConfigHelper::getTranslationKey('entity', 'label', $targetEntityClass, $relationName)
        );
        $description = ConfigHelper::getTranslationKey('entity', 'description', $targetEntityClass, $relationName);

        $targetEntityPrimaryKeyColumns = $this->getPrimaryKeyColumnNames($targetEntityClass);
        $targetFieldName               = array_shift($targetEntityPrimaryKeyColumns);

        // create field
        $this->relationBuilder->addFieldConfig(
            $sourceEntityClass,
            $relationName,
            'manyToOne',
            [
                'extend' => [
                    'owner'         => ExtendScope::OWNER_SYSTEM,
                    'state'         => ExtendScope::STATE_NEW,
                    'extend'        => true,
                    'target_entity' => $targetEntityClass,
                    'target_field'  => $targetFieldName,
                    'relation_key'  => $relationKey,
                ],
                'entity' => [
                    'label'       => $label,
                    'description' => $description,
                ],
                'view'   => [
                    'is_displayable' => false
                ],
                'form'   => [
                    'is_enabled' => false
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

        // add relation to target entity
        $this->relationBuilder->addManyToOneRelationTargetSide(
            $targetEntityClass,
            $sourceEntityClass,
            $relationName,
            $relationKey
        );
    }

    /**
     * @param string $entityClass
     *
     * @return string[]
     */
    protected function getPrimaryKeyColumnNames($entityClass)
    {
        try {
            return $this->getConfigManager()
                ->getEntityManager()
                ->getClassMetadata($entityClass)
                ->getIdentifierColumnNames();
        } catch (\ReflectionException $e) {
            // ignore entity not found exception
            return ['id'];
        } catch (MappingException $e) {
            // ignore any doctrine mapping exceptions
            // it may happens if the entity has relation to deleted custom entity
            return ['id'];
        }
    }
}
