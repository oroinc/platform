<?php

namespace Oro\Bundle\NoteBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
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
        $entitiesWithNotes = $this->getNotesEnabledFor();

        return !empty($entitiesWithNotes);
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

            $this->addManyToOneRelation($entityName, $relationName, $relationKey);
            $this->addManyToOneRelation($noteClassName, $relationName, $relationKey, true);
        }


        //$this->extendConfigProvider->flush();
    }

    /**
     * @param string $targetEntityName
     * @param string $relationName
     * @param string $relationKey
     * @param bool   $isOwningSide
     */
    protected function addManyToOneRelation($targetEntityName, $relationName, $relationKey, $isOwningSide = false)
    {
        $noteClassName = self::NOTE_ENTITY;
        $entityConfig  = $this->extendConfigProvider->getConfig($targetEntityName);

        $fieldId = new FieldConfigId('extend', $noteClassName, $relationName, 'manyToOne');
        if ($isOwningSide) {
            $assign        = true;
            $owner         = true;
            $targetFieldId = false;

            // update schema info
            $schema = $entityConfig->get('schema', false, []);
            $schema['relation'][$relationName] = $relationName;
            $entityConfig->set('schema', $schema);

            // update index info
            $index = $entityConfig->get('index', false, []);
            $index[$relationName] = $relationName;
            $entityConfig->set('index', $index);
        } else {
            $assign        = false;
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
            'target_entity'   => $noteClassName,
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
