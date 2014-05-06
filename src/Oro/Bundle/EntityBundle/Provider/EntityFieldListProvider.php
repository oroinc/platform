<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;

class EntityFieldListProvider extends EntityFieldRecursiveProvider
{
    /** @var string|null */
    protected $currentClassName = null;

    /** @var string|null */
    protected $rootClassName = null;

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
    protected function addField(array &$result, $name, $type, $label, $isIdentifier, $translate)
    {
        $field = [
            'name'  => $name,
            'type'  => $type,
            'label' => $translate ? $this->translator->trans($label) : $label
        ];

        if ($isIdentifier) {
            $field['identifier'] = true;
        }

        if (empty($result[$this->currentClassName])) {
            $result[$this->currentClassName] = array_merge(
                $this->addEntityDetails($this->currentClassName, [], $translate),
                ['fields' => []]
            );
        }

        $result[$this->currentClassName]['fields'][] = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields(
        $entityName,
        $withRelations = false,
        $withVirtualFields = false,
        $withEntityDetails = false,
        $withUnidirectional = false,
        $deepLevel = 0,
        $lastDeepLevelRelations = false,
        $translate = true
    ) {
        $this->currentClassName = $this->entityClassResolver->getEntityClass($entityName);
        if (is_null($this->rootClassName)) {
            $this->rootClassName = $this->currentClassName;
        }

        return parent::getFields($entityName, $withRelations, $withVirtualFields, $withEntityDetails, $withUnidirectional, $deepLevel, $lastDeepLevelRelations, $translate);
    }

    /**
     * {@inheritdoc}
     */
    protected function sortFields(array &$fields)
    {
        // nothing to sort here
    }

    /**
     * {@inheritdoc}
     */
    protected function addRelation(
        array &$result,
        array $relation,
        $withVirtualFields,
        $withEntityDetails,
        $withUnidirectional,
        $relationDeepLevel,
        $lastDeepLevelRelations,
        $translate
    ) {
        $name = $relation['name'];
        if ($translate) {
            $relation['label'] = $this->translator->trans($relation['label']);
        }

        $relatedEntityName = $relation['related_entity_name'];

        // add related entity with fields to field list
        if (empty($result[$relatedEntityName])) {
            $result[$relatedEntityName] = array_merge(
                $this->entityProvider->getEntity($relatedEntityName),
                ['fields' => []]
            );
        }

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
                    $withVirtualFields,
                    $withEntityDetails,
                    $withUnidirectional,
                    $relationDeepLevel,
                    $lastDeepLevelRelations,
                    $translate
                );

            $result[$relatedEntityName]['fields'] = array_merge_recursive(
                $result[$relatedEntityName]['fields'],
                $relatedEntityFields[$relatedEntityName]['fields']
            );
        }

        // add field relation to source entity
        $result[$this->rootClassName]['fields'][] = [
            'name'          => $relation['name'],
            'label'         => $relation['label'],
            'relation_type' => $relation['relation_type'],
            'related_entity_name' => $relatedEntityName,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function addEntityDetails($entityName, array $entityData, $translate)
    {
        $entity = $this->entityProvider->getEntity($entityName, $translate);

        return array_merge_recursive($entityData, $entity);
    }
}