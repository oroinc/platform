<?php

namespace Oro\Bundle\NoteBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\BaseDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class NoteDumperExtension extends BaseDumperExtension
{
    const NOTE_CONFIG_SCOPE = 'note';
    const NOTE_ENTITY       = 'Oro\Bundle\NoteBundle\Entity\Note';

    /**
     * @return string
     */
    protected function getScopeName()
    {
        return self::NOTE_CONFIG_SCOPE;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType, array $extendConfigs)
    {
        if ($actionType == ExtendConfigDumper::ACTION_PRE_UPDATE) {
            $entitiesWithNotes = $this->getClassNamesWithFlagEnabled($this->scopedConfigProvider, 'enabled');

            return !empty($entitiesWithNotes);
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(array &$extendConfigs)
    {
        $noteClassName = self::NOTE_ENTITY;

        $withNotes = $this->getClassNamesWithFlagEnabled($this->scopedConfigProvider, 'enabled');
        foreach ($withNotes as $entityName) {
            $relationName = $this->getRelationName($entityName);
            $relationKey  = $this->getRelationKey($noteClassName, $entityName, $relationName);

            $entityConfig = $this->extendConfigProvider->getConfig($entityName);
            $entity       = $entityConfig->get('entity');

            $entityLabel = empty($entity['label']) ? sprintf('oro.note.%s.label', $relationName) : $entity['label'];
            $entityDescription = empty($entity['description']) ?
                sprintf('oro.note.%s.description', $relationName) : $entity['description'];

            // create field
            $this->createField(
                $noteClassName,
                $relationName,
                'manyToOne',
                $entityName,
                $relationKey,
                [
                    'entity' => [
                        'label'       => $entityLabel,
                        'description' => $entityDescription,
                    ],
                ]
            );

            // add relation to target entity side
            $this->addManyToOneRelation($entityName, $noteClassName, $relationName, $relationKey);

            // add relation to note, source entity side
            $this->addManyToOneRelation($noteClassName, $entityName, $relationName, $relationKey, true);
        }
    }
}
