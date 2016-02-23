<?php

namespace Oro\Bundle\EntityExtendBundle\ORM;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class RelationMetadataBuilder implements MetadataBuilderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /**
     * @param ConfigManager                   $configManager
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
            /** @var FieldConfigId $fieldId */
            $fieldId = $relation['field_id'];
            if ($fieldId && isset($schema['relation'][$fieldId->getFieldName()])) {
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
        $targetEntity   = $relation['target_entity'];
        $targetIdColumn = isset($relation['target_id_column']) ? $relation['target_id_column'] : 'id';
        $cascade        = !empty($relation['cascade']) ? $relation['cascade'] : [];
        $cascade[]      = 'detach';

        $builder = $metadataBuilder->createManyToOne($fieldId->getFieldName(), $targetEntity);
        if (!empty($relation['target_field_id'])) {
            $builder->inversedBy($relation['target_field_id']->getFieldName());
        }
        $builder->addJoinColumn(
            $this->getManyToOneColumnName($fieldId, $targetIdColumn),
            $targetIdColumn,
            true,
            false,
            'SET NULL'
        );
        foreach ($cascade as $cascadeType) {
            $builder->{'cascade' . ucfirst($cascadeType)}();
        }
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

        $cascade   = !empty($relation['cascade']) ? $relation['cascade'] : [];
        $cascade[] = 'detach';

        $builder = $metadataBuilder->createOneToMany($fieldId->getFieldName(), $targetEntity);
        if (!empty($relation['target_field_id'])) {
            $builder->mappedBy($relation['target_field_id']->getFieldName());
        }
        foreach ($cascade as $cascadeType) {
            $builder->{'cascade' . ucfirst($cascadeType)}();
        }
        $builder->build();

        if (!$relation['owner']
            && RelationType::ONE_TO_MANY === ExtendHelper::getRelationType($relationKey)
            && $this->isDefaultRelationRequired($fieldId)
        ) {
            $targetIdColumn = isset($relation['target_id_column']) ? $relation['target_id_column'] : 'id';
            $this->buildDefaultRelation($metadataBuilder, $fieldId, $targetEntity, $targetIdColumn);
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
                $targetIdColumn = isset($relation['target_id_column']) ? $relation['target_id_column'] : 'id';
                $this->buildDefaultRelation($metadataBuilder, $fieldId, $targetEntity, $targetIdColumn);
            }
        } elseif (!empty($relation['target_field_id'])) {
            $this->buildManyToManyTargetSideRelation(
                $metadataBuilder,
                $fieldId,
                $targetEntity,
                $relation['target_field_id']
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

        $cascade = !empty($relation['cascade']) ? $relation['cascade'] : [];

        $builder = $metadataBuilder->createManyToMany($fieldId->getFieldName(), $targetEntity);
        if (!empty($relation['target_field_id'])) {
            $builder->inversedBy($relation['target_field_id']->getFieldName());
        }
        $builder->setJoinTable(
            $this->nameGenerator->generateManyToManyJoinTableName(
                $fieldId->getClassName(),
                $fieldId->getFieldName(),
                $targetEntity
            )
        );
        foreach ($cascade as $cascadeType) {
            $builder->{'cascade' . ucfirst($cascadeType)}();
        }
        $builder->build();
    }

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param FieldConfigId        $fieldId
     * @param string               $targetEntity
     * @param FieldConfigId        $targetFieldId
     */
    protected function buildManyToManyTargetSideRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        $targetEntity,
        FieldConfigId $targetFieldId
    ) {
        $metadataBuilder->addInverseManyToMany(
            $fieldId->getFieldName(),
            $targetEntity,
            $targetFieldId->getFieldName()
        );
    }

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param FieldConfigId        $fieldId
     * @param string               $targetEntity
     * @param string               $targetIdColumn
     *
     */
    protected function buildDefaultRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        $targetEntity,
        $targetIdColumn
    ) {
        $builder = $metadataBuilder->createOneToOne(
            ExtendConfigDumper::DEFAULT_PREFIX . $fieldId->getFieldName(),
            $targetEntity
        );

        $builder->addJoinColumn(
            $this->nameGenerator->generateRelationDefaultColumnName($fieldId->getFieldName()),
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
            $columnName =
                $this->nameGenerator->generateRelationColumnName($fieldId->getFieldName(), '_' . $targetIdColumn);
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
}
