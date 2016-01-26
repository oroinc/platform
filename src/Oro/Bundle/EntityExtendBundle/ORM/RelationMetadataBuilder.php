<?php

namespace Oro\Bundle\EntityExtendBundle\ORM;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class RelationMetadataBuilder implements MetadataBuilderInterface
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /**
     * @param ConfigProvider                  $extendConfigProvider
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(
        ConfigProvider $extendConfigProvider,
        ExtendDbIdentifierNameGenerator $nameGenerator
    ) {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->nameGenerator        = $nameGenerator;
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
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function build(ClassMetadataBuilder $metadataBuilder, ConfigInterface $extendConfig)
    {
        $relations = $extendConfig->get('relation', false, []);
        $schema    = $extendConfig->get('schema', false, []);
        foreach ($relations as $relationKey => $relation) {
            /** @var FieldConfigId $fieldId */
            $fieldId = $relation['field_id'];
            if ($fieldId && isset($schema['relation'][$fieldId->getFieldName()])) {
                $targetEntity = $relation['target_entity'];
                /** @var FieldConfigId|null $targetFieldId */
                $targetFieldId = !empty($relation['target_field_id']) ? $relation['target_field_id'] : null;
                $cascade       = !empty($relation['cascade']) ? $relation['cascade'] : [];

                switch ($fieldId->getFieldType()) {
                    case RelationType::MANY_TO_ONE:
                        $cascade[] = 'detach';
                        $this->buildManyToOneRelation(
                            $metadataBuilder,
                            $fieldId,
                            $targetEntity,
                            $targetFieldId,
                            $cascade
                        );
                        break;
                    case RelationType::ONE_TO_MANY:
                        $cascade[] = 'detach';
                        $this->buildOneToManyRelation(
                            $metadataBuilder,
                            $fieldId,
                            $targetEntity,
                            $targetFieldId,
                            $cascade
                        );
                        if (!$relation['owner']
                            && RelationType::ONE_TO_MANY === ExtendHelper::getRelationType($relationKey)
                            && $this->isDefaultRelationRequired($fieldId)
                        ) {
                            $this->buildDefaultRelation($metadataBuilder, $fieldId, $targetEntity);
                        }
                        break;
                    case RelationType::MANY_TO_MANY:
                        if ($relation['owner']) {
                            $this->buildManyToManyOwningSideRelation(
                                $metadataBuilder,
                                $fieldId,
                                $targetEntity,
                                $targetFieldId,
                                $cascade
                            );
                            if ($this->isDefaultRelationRequired($fieldId)) {
                                $this->buildDefaultRelation($metadataBuilder, $fieldId, $targetEntity);
                            }
                        } elseif ($targetFieldId) {
                            $this->buildManyToManyTargetSideRelation(
                                $metadataBuilder,
                                $fieldId,
                                $targetEntity,
                                $targetFieldId
                            );
                        }
                        break;
                }
            }
        }
    }

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param FieldConfigId        $fieldId
     * @param string               $targetEntity
     * @param FieldConfigId|null   $targetFieldId
     * @param string[]             $cascade
     */
    protected function buildManyToOneRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        $targetEntity,
        FieldConfigId $targetFieldId = null,
        array $cascade = []
    ) {
        $builder = $metadataBuilder->createManyToOne($fieldId->getFieldName(), $targetEntity);
        if ($targetFieldId) {
            $builder->inversedBy($targetFieldId->getFieldName());
        }
        $builder->addJoinColumn(
            $this->getManyToOneColumnName($fieldId),
            'id',
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
     * @param string               $targetEntity
     * @param FieldConfigId|null   $targetFieldId
     * @param string[]             $cascade
     */
    protected function buildOneToManyRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        $targetEntity,
        FieldConfigId $targetFieldId = null,
        array $cascade = []
    ) {
        $builder = $metadataBuilder->createOneToMany($fieldId->getFieldName(), $targetEntity);
        if ($targetFieldId) {
            $builder->mappedBy($targetFieldId->getFieldName());
        }
        foreach ($cascade as $cascadeType) {
            $builder->{'cascade' . ucfirst($cascadeType)}();
        }
        $builder->build();
    }

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param FieldConfigId        $fieldId
     * @param string               $targetEntity
     * @param FieldConfigId|null   $targetFieldId
     * @param string[]             $cascade
     */
    protected function buildManyToManyOwningSideRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        $targetEntity,
        FieldConfigId $targetFieldId = null,
        array $cascade = []
    ) {
        $builder = $metadataBuilder->createManyToMany($fieldId->getFieldName(), $targetEntity);
        if ($targetFieldId) {
            $builder->inversedBy($targetFieldId->getFieldName());
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
     */
    protected function buildDefaultRelation(
        ClassMetadataBuilder $metadataBuilder,
        FieldConfigId $fieldId,
        $targetEntity
    ) {
        $builder = $metadataBuilder->createOneToOne(
            ExtendConfigDumper::DEFAULT_PREFIX . $fieldId->getFieldName(),
            $targetEntity
        );
        $builder->addJoinColumn(
            $this->nameGenerator->generateRelationDefaultColumnName($fieldId->getFieldName()),
            'id',
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
        return !$this->extendConfigProvider->getConfigById($fieldId)->is('without_default');
    }

    /**
     * @param FieldConfigId $fieldId
     *
     * @return string
     */
    protected function getManyToOneColumnName(FieldConfigId $fieldId)
    {
        $columnName = null;
        if ($this->extendConfigProvider->hasConfigById($fieldId)) {
            $columnName = $this->extendConfigProvider->getConfigById($fieldId)->get('column_name');
        }
        if (!$columnName) {
            $columnName = $this->nameGenerator->generateRelationColumnName($fieldId->getFieldName());
        }

        return $columnName;
    }
}
