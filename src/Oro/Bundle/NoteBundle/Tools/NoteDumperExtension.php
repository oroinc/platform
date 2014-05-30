<?php

namespace Oro\Bundle\NoteBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ConfigDumperExtension;

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
            $this->addManyToOneRelation($entityName, $relationName);
            $this->addManyToOneRelation($noteClassName, $relationName, true);
        }

        $this->extendConfigProvider->flush();
    }

    protected function addManyToOneRelation($targetEntityName, $relationName, $isOwningSide = false)
    {
        $noteClassName = self::NOTE_ENTITY;
        $entityConfig  = $this->extendConfigProvider->getConfig($targetEntityName);

        $relations    = $entityConfig->get('relation', false, []);
        $fieldName    = $relationName;
        $relationType = 'manyToOne';
        $relationKey  = sprintf('%s|%s|%s|%s', $relationType, $noteClassName, $targetEntityName, $relationName);

        $fieldId = new FieldConfigId('extend', $noteClassName, $fieldName, $relationType);
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
            $entityConfig->set('index', $schema);
        } else {
            $assign        = false;
            $owner         = false;
            $targetFieldId = $fieldId;
            $fieldId       = false;
        }

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
     * @param string $entityName full name
     *
     * @return string
     */
    protected function getRelationName($entityName)
    {
        $names = explode('\\', $entityName);
        return strtolower(array_pop($names)) . 's';
    }
}
