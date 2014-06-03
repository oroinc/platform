<?php

namespace Oro\Bundle\NoteBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class NoteDumperExtension extends ConfigDumperExtension
{
    const NOTE_CONFIG_SCOPE = 'note';
    const NOTE_ENTITY       = 'Oro\Bundle\NoteBundle\Entity\Note';

    /** @var ConfigProvider */
    protected $noteConfigProvider;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var null|array entity class names list that have notes enabled */
    protected $notesEnabledFor = null;

    public function __construct(ConfigManager $configManager)
    {
        $this->extendConfigProvider = $configManager->getProvider('extend');
        $this->noteConfigProvider   = $configManager->getProvider(self::NOTE_CONFIG_SCOPE);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType, ConfigProvider $extendProvider, array $extendConfigs)
    {
        if ($actionType == ConfigDumperExtension::ACTION_PRE_UPDATE) {
            $entitiesWithNotes = $this->getNotesEnabledFor();

            return !empty($entitiesWithNotes);
        } else {
            return false;
        }
    }

    /**
     * Class names list that have notes enabled
     *
     * @return array
     */
    protected function getNotesEnabledFor()
    {
        if (is_null($this->notesEnabledFor)) {
            $configs = $this->noteConfigProvider->getConfigs();
            $this->notesEnabledFor = [];

            foreach ($configs as $config) {
                if (1 === $config->get('enabled')) {
                    $this->notesEnabledFor[] = $config->getId()->getClassName();
                }
            }
        }

        return $this->notesEnabledFor;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(ConfigProvider $extendProvider, array &$extendConfigs)
    {
        $noteClassName = self::NOTE_ENTITY;

        $withNotes = $this->getNotesEnabledFor();
        foreach ($withNotes as $entityName) {
            $relationName = $this->getRelationName($entityName);
            $relationKey  = $this->getRelationKey($noteClassName, $entityName, $relationName);

            // create field
            $this->createField($noteClassName, $relationName, 'manyToOne', $entityName, $relationKey);

            // add relation to target entity side
            $this->addManyToOneRelation($entityName, $noteClassName, $relationName, $relationKey);

            // add relation to note, source entity side
            $this->addManyToOneRelation($noteClassName, $entityName, $relationName, $relationKey, true);
        }
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param string $targetEntityName
     * @param string $relationKey
     */
    protected function createField($className, $fieldName, $fieldType, $targetEntityName, $relationKey)
    {
        $cm = $this->extendConfigProvider->getConfigManager();

        $cm->createConfigFieldModel($className, $fieldName, $fieldType);
        $this->updateFieldConfig(
            $cm,
            'extend',
            $className,
            $fieldName,
            [
                'owner'         => ExtendScope::OWNER_SYSTEM,
                'state'         => ExtendScope::STATE_NEW,
                'is_extend'     => false,
                'extend'        => true,
                'is_deleted'    => false,
                'is_inverse'    => false,
                'target_entity' => $targetEntityName,
                'target_field'  => 'id',
                'relation_key'  => $relationKey,
            ]
        );

        $classAlias = ExtendHelper::buildAssociationName($className);
        $this->updateFieldConfig(
            $cm,
            'entity',
            $className,
            $fieldName,
            [
                'label'       => sprintf('oro.%s.%s.label', $classAlias, $fieldName),
                'description' => sprintf('oro.%s.%s.label', $classAlias, $fieldName),
            ]
        );
        $this->updateFieldConfig(
            $cm,
            'view',
            $className,
            $fieldName,
            [
                'is_displayable' => false
            ]
        );
        $this->updateFieldConfig(
            $cm,
            'form',
            $className,
            $fieldName,
            [
                'is_enabled' => true
            ]
        );
        $this->updateFieldConfig(
            $cm,
            'dataaudit',
            $className,
            $fieldName,
            [
                'auditable' => false
            ]
        );
    }

    protected function updateFieldConfig(
        ConfigManager $configManager,
        $scope,
        $className,
        $fieldName,
        array $values
    ) {
        $configProvider = $configManager->getProvider($scope);
        $fieldConfig    = $configProvider->getConfig($className, $fieldName);
        foreach ($values as $code => $val) {
            $fieldConfig->set($code, $val);
        }
        $configManager->persist($fieldConfig);
        $configManager->calculateConfigChangeSet($fieldConfig);
    }

    /**
     * @param string $targetEntityName
     * @param string $sourceEntityName
     * @param string $relationName
     * @param string $relationKey
     * @param bool   $isOwningSide
     */
    protected function addManyToOneRelation(
        $targetEntityName,
        $sourceEntityName,
        $relationName,
        $relationKey,
        $isOwningSide = false
    ) {
        $entityConfig  = $this->extendConfigProvider->getConfig($targetEntityName);

        $fieldId = new FieldConfigId('extend', self::NOTE_ENTITY, $relationName, 'manyToOne');
        $assign        = false;
        if ($isOwningSide) {
            $owner         = true;
            $targetFieldId = false;

            // update schema info
            $schema = $entityConfig->get('schema', false, []);
            $schema['relation'][$relationName] = $relationName;
            $entityConfig->set('schema', $schema);

            // update index info
            $index = $entityConfig->get('index', false, []);
            $index[$relationName] = null;
            $entityConfig->set('index', $index);
        } else {
            $owner         = false;
            $targetFieldId = $fieldId;
            $fieldId       = false;
        }

        $relations    = $entityConfig->get('relation', false, []);

        // add relation to config
        $relations[$relationKey] = [
            'assign'          => $assign,
            'field_id'        => $fieldId,
            'owner'           => $owner,
            'target_entity'   => $sourceEntityName,
            'target_field_id' => $targetFieldId
        ];
        $entityConfig->set('relation', $relations);

        $this->extendConfigProvider->persist($entityConfig);
    }

    /**
     * @param string $fromEntityClassName
     * @param string $entityClassName
     * @param string $relationFieldName
     *
     * @return string "manyToOne|Oro\Bundle\NoteBundle\Entity\Note|Oro\Bundle\UserBundle\Entity\User|user"
     */
    protected function getRelationKey($fromEntityClassName, $entityClassName, $relationFieldName)
    {
        return ExtendHelper::buildRelationKey($fromEntityClassName, $relationFieldName, 'manyToOne', $entityClassName);
    }

    /**
     * @param string $entityClassName
     *
     * @return string
     */
    protected function getRelationName($entityClassName)
    {
        return ExtendHelper::buildAssociationName($entityClassName);
    }
}
