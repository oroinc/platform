<?php

namespace Oro\Bundle\EntityExtendBundle\ORM;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Mapping\Builder\AssociationBuilder;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Exception\InvalidRelationEntityException;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Builds Doctrines metadata for relations.
 */
class RelationMetadataBuilder implements MetadataBuilderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /**
     * RelationMetadataBuilder constructor.
     *
     * @param ConfigManager $configManager
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(
        ConfigManager $configManager,
        ExtendDbIdentifierNameGenerator $nameGenerator
    ) {
        $this->configManager = $configManager;
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ConfigInterface $extendConfig)
    {
        return $extendConfig->is('relation');
    }

    /**
     * {@inheritdoc}
     */
    public function build(ClassMetadataBuilder $metadataBuilder, ConfigInterface $extendConfig)
    {
        $relations = $extendConfig->get('relation', false, []);
        $schema    = $extendConfig->get('schema', false, []);
        foreach ($relations as $relationKey => $relation) {
            $configRelationEntity = $this->configManager->getEntityConfig('extend', $relation['target_entity']);

            /** @var FieldConfigId $fieldId */
            $fieldId = $relation['field_id'];
            if ($fieldId
                && isset($schema['relation'][$fieldId->getFieldName()])
                && !$configRelationEntity->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_DELETE])
            ) {
                switch ($fieldId->getFieldType()) {
                    case RelationType::MANY_TO_ONE:
                        $this->buildManyToOneRelation($metadataBuilder, $fieldId, $relation);
                        break;
                    case RelationType::ONE_TO_MANY:
                        $this->buildOneToManyRelation($metadataBuilder, $fieldId, $relation, $relationKey);
                        break;
                    case RelationType::MANY_TO_MANY:
                        $this->buildManyToManyRelation($metadataBuilder, $fieldId, $relation);
                        break;
                }
            }
        }
    }

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param FieldConfigId        $fieldId
     * @param array                $relation
     */
    protected function buildManyToOneRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        array $relation
    ) {
        $targetEntity = $relation['target_entity'];
        $targetIdColumn = $this->getSinglePrimaryKeyColumn($targetEntity);
        $cascade = $this->getCascadeOption($relation);
        $cascade[] = 'detach';

        $builder = $metadataBuilder->createManyToOne($fieldId->getFieldName(), $targetEntity);
        if (!empty($relation['target_field_id'])) {
            $builder->inversedBy($relation['target_field_id']->getFieldName());
        }
        $builder->addJoinColumn(
            $this->getManyToOneColumnName($fieldId, $targetIdColumn),
            $targetIdColumn,
            $this->getNullableOption($relation),
            false,
            $this->getOnDeleteOption($relation)
        );
        $this->setCascadeOptions($builder, $cascade);
        $this->setFetchOption($builder, $this->getFetchOption($relation));
        $builder->build();
    }

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param FieldConfigId        $fieldId
     * @param array                $relation
     * @param string               $relationKey
     */
    protected function buildOneToManyRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        array $relation,
        $relationKey
    ) {
        $targetEntity = $relation['target_entity'];
        $cascade = $this->getCascadeOption($relation);
        $cascade[] = 'detach';

        $builder = $metadataBuilder->createOneToMany($fieldId->getFieldName(), $targetEntity);
        if (!empty($relation['target_field_id'])) {
            $builder->mappedBy($relation['target_field_id']->getFieldName());
        }
        $this->setCascadeOptions($builder, $cascade);
        $this->setFetchOption($builder, $this->getFetchOption($relation));
        if (isset($relation['orphanRemoval']) && $relation['orphanRemoval']) {
            $builder->orphanRemoval();
        }

        $builder->build();

        if (!$relation['owner']
            && RelationType::ONE_TO_MANY === ExtendHelper::getRelationType($relationKey)
            && $this->isDefaultRelationRequired($fieldId)
        ) {
            $this->buildDefaultRelation($metadataBuilder, $fieldId, $targetEntity);
        }
    }

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param FieldConfigId        $fieldId
     * @param array                $relation
     */
    protected function buildManyToManyRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        array $relation
    ) {
        $targetEntity = $relation['target_entity'];

        if ($relation['owner']) {
            $this->buildManyToManyOwningSideRelation($metadataBuilder, $fieldId, $relation);
            if ($this->isDefaultRelationRequired($fieldId)) {
                $this->buildDefaultRelation($metadataBuilder, $fieldId, $targetEntity);
            }
        } elseif (!empty($relation['target_field_id'])) {
            $this->buildManyToManyTargetSideRelation(
                $metadataBuilder,
                $fieldId,
                $targetEntity,
                $relation
            );
        }
    }

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param FieldConfigId        $fieldId
     * @param array                $relation
     */
    protected function buildManyToManyOwningSideRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        array $relation
    ) {
        $targetEntity = $relation['target_entity'];

        $builder = $metadataBuilder->createManyToMany($fieldId->getFieldName(), $targetEntity);
        if (!empty($relation['target_field_id'])) {
            $builder->inversedBy($relation['target_field_id']->getFieldName());
        }
        $entityClassName = $fieldId->getClassName();
        $joinTableName   = $this->nameGenerator->generateManyToManyJoinTableName(
            $entityClassName,
            $fieldId->getFieldName(),
            $targetEntity
        );
        $selfIdColumn = $this->getSinglePrimaryKeyColumn($entityClassName);
        $targetIdColumn = $this->getSinglePrimaryKeyColumn($targetEntity);
        $selfJoinTableColumnNamePrefix = null;
        $targetJoinTableColumnNamePrefix = null;
        if ($entityClassName === $targetEntity) {
            // fix the collision of names if owning side entity equals to inverse side entity
            $selfJoinTableColumnNamePrefix = 'src_';
            $targetJoinTableColumnNamePrefix = 'dest_';
        }
        $selfJoinTableColumnName = $this->nameGenerator->generateManyToManyJoinTableColumnName(
            $entityClassName,
            '_' . $selfIdColumn,
            $selfJoinTableColumnNamePrefix
        );
        $targetJoinTableColumnName = $this->nameGenerator->generateManyToManyJoinTableColumnName(
            $targetEntity,
            '_' . $targetIdColumn,
            $targetJoinTableColumnNamePrefix
        );
        $builder->setJoinTable($joinTableName);
        $builder->addJoinColumn($selfJoinTableColumnName, $selfIdColumn, false, false, 'CASCADE');
        $builder->addInverseJoinColumn($targetJoinTableColumnName, $targetIdColumn, false, false, 'CASCADE');
        $this->setCascadeOptions($builder, $this->getCascadeOption($relation));
        $this->setFetchOption($builder, $this->getFetchOption($relation));
        $builder->build();
    }

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param FieldConfigId        $fieldId
     * @param string               $targetEntity
     * @param array                $relation
     */
    protected function buildManyToManyTargetSideRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        $targetEntity,
        array $relation
    ) {
        $builder = $metadataBuilder->createManyToMany($fieldId->getFieldName(), $targetEntity);
        $builder->mappedBy($relation['target_field_id']->getFieldName());
        $this->setCascadeOptions($builder, $this->getCascadeOption($relation));
        $this->setFetchOption($builder, $this->getFetchOption($relation));
        $builder->build();
    }

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param FieldConfigId        $fieldId
     * @param string               $targetEntity
     *
     */
    protected function buildDefaultRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        $targetEntity
    ) {
        $targetIdColumn = $this->getSinglePrimaryKeyColumn($targetEntity);
        $builder        = $metadataBuilder->createManyToOne(
            ExtendConfigDumper::DEFAULT_PREFIX . $fieldId->getFieldName(),
            $targetEntity
        );
        $builder->addJoinColumn(
            $this->nameGenerator->generateRelationDefaultColumnName(
                $fieldId->getFieldName(),
                '_' . $targetIdColumn
            ),
            $targetIdColumn,
            true,
            false,
            'SET NULL'
        );
        $builder->build();
    }

    /**
     * @param FieldConfigId $fieldId
     *
     * @return bool
     */
    protected function isDefaultRelationRequired(FieldConfigId $fieldId)
    {
        return !$this->getFieldConfig($fieldId)->is('without_default');
    }

    /**
     * @param FieldConfigId $fieldId
     * @param string        $targetIdColumn
     *
     * @return string
     */
    protected function getManyToOneColumnName(FieldConfigId $fieldId, $targetIdColumn)
    {
        $columnName = null;
        if ($this->configManager->hasConfig($fieldId->getClassName(), $fieldId->getFieldName())) {
            $columnName = $this->getFieldConfig($fieldId)->get('column_name');
        }
        if (!$columnName) {
            $columnName = $this->nameGenerator->generateRelationColumnName(
                $fieldId->getFieldName(),
                '_' . $targetIdColumn
            );
        }

        return $columnName;
    }

    /**
     * @param FieldConfigId $fieldId
     *
     * @return ConfigInterface
     */
    protected function getFieldConfig(FieldConfigId $fieldId)
    {
        return $this->configManager->getFieldConfig(
            'extend',
            $fieldId->getClassName(),
            $fieldId->getFieldName()
        );
    }

    /**
     * @param string $entityName
     *
     * @return string
     */
    protected function getSinglePrimaryKeyColumn($entityName)
    {
        $pkColumns = ['id'];
        if ($this->configManager->hasConfig($entityName)) {
            $entityConfig = $this->configManager->getEntityConfig('extend', $entityName);
            $pkColumns = $entityConfig->get('pk_columns', false, $pkColumns);
            if (count($pkColumns) > 1) {
                // Currently we don't support composite primary keys.
                // When support will be implemented, this restriction should be removed.
                // Task id: BAP-9815
                throw new InvalidRelationEntityException(
                    sprintf('Entity class %s has composite primary key.', $entityName)
                );
            }
        }

        return reset($pkColumns);
    }

    /**
     * @param array $relation
     *
     * @return string
     */
    private function getOnDeleteOption(array $relation)
    {
        if (empty($relation['on_delete'])) {
            return 'SET NULL';
        }

        return $relation['on_delete'];
    }

    /**
     * @param array $relation
     *
     * @return bool
     */
    private function getNullableOption(array $relation)
    {
        if (!array_key_exists('nullable', $relation)) {
            return true;
        }

        $nullable = $relation['nullable'];
        if (null === $nullable) {
            $nullable = true;
        }

        return $nullable;
    }

    /**
     * @param array $relation
     *
     * @return string[]
     */
    private function getCascadeOption(array $relation)
    {
        if (empty($relation['cascade'])) {
            return [];
        }

        return $relation['cascade'];
    }

    /**
     * @param array $relation
     *
     * @return string
     */
    private function getFetchOption(array $relation)
    {
        if (empty($relation['fetch'])) {
            return '';
        }

        return $relation['fetch'];
    }

    /**
     * @param AssociationBuilder $builder
     * @param string[]           $cascades
     */
    private function setCascadeOptions(AssociationBuilder $builder, array $cascades)
    {
        foreach ($cascades as $cascade) {
            $builder->{'cascade' . ucfirst($cascade)}();
        }
    }

    /**
     * @param AssociationBuilder $builder
     * @param string             $fetch
     */
    private function setFetchOption(AssociationBuilder $builder, string $fetch)
    {
        $method = Inflector::camelize('fetch_' . $fetch);

        if (method_exists($builder, $method)) {
            $builder->$method();
        }
    }
}
