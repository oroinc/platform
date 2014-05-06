<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;

class EntityFieldListProvider extends EntityFieldRecursiveProvider
{
    /**
     * {@inheritdoc}
     */
    public function isApplied(ParameterBag $parameters)
    {
        $entityName  = str_replace('_', '\\', $parameters->get('entityName'));
        $isPlainList = ('1' === $parameters->get('plain-list'));

        return !empty($entityName) && $isPlainList;
    }

    /**
     * {@inheritdoc}
     */
    protected function addRelation(
        array &$result,
        array $relation,
        $withEntityDetails,
        $relationDeepLevel,
        $lastDeepLevelRelations,
        $translate
    ) {
        $name = $relation['name'];
        if ($translate) {
            $relation['label'] = $this->translator->trans($relation['label']);
        }

        $relatedEntityName = $relation['related_entity_name'];
        $relation = $this->addEntityDetails($relatedEntityName, $relation, $translate);

        if ($relationDeepLevel >= 0) {
            // set some exceptions
            // todo: we need to find more proper way to do this
            if ($relationDeepLevel > 0 && ($name === 'owner' || $name === 'createdBy' || $name === 'updatedBy')) {
                $relationDeepLevel = 0;
            }

            $relatedEntityFields =
                $this->getFields(
                    $relatedEntityName,
                    $withEntityDetails && ($relationDeepLevel > 0 || $lastDeepLevelRelations),
                    $withEntityDetails,
                    $relationDeepLevel,
                    $lastDeepLevelRelations,
                    $translate
                );
        } else {
            $relatedEntityFields = [];
        }

        // add related entity with fields to field list
        if (empty($result[$relatedEntityName])) {
            $result[$relatedEntityName] = array_merge(
                $this->entityProvider->getEntity($relatedEntityName),
                ['fields' => $relatedEntityFields]
            );
        }

        $sourceClassName = $relation['source_entity_name'];

        // add field relation to source entity
        $result[$sourceClassName]['fields'] = array_merge(
            $result[$sourceClassName]['fields'],
            [
                'name'          => $relation['name'],
                'label'         => $relation['label'],
                'relation_type' => $relation['relation_type'],
                'related_entity_name' => $relatedEntityName,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function addEntityDetails($relatedEntityName, array $relation, $translate)
    {
        $entity = $this->entityProvider->getEntity($relatedEntityName, $translate);
        foreach ($entity as $key => $val) {
            if (!in_array($key, ['name'])) {
                $relation[$key] = $val;
            }
        }

        return $relation;
    }
} 