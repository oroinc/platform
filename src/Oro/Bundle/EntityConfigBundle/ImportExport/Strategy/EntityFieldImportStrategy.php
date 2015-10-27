<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Strategy;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

class EntityFieldImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        $existingEntity = parent::findExistingEntity($entity, $searchContext);

        if (!$existingEntity and $entity instanceof FieldConfigModel) {
            $existingEntity = $this->databaseHelper->findOneBy(
                'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel',
                [
                    'fieldName' => $entity->getFieldName(),
                    'type' => $entity->getType(),
                    'entity' => $entity->getEntity()
                ]
            );
        }

        return $existingEntity;
    }

    /**
     * {@inheritdoc}
     */
    protected function importExistingEntity(
        $entity,
        $existingEntity,
        $itemData = null,
        array $excludedFields = array()
    ) {
        $excludedFields[] = 'fieldName';
        $excludedFields[] = 'type';
        $excludedFields[] = 'entity';

        parent::importExistingEntity($entity, $existingEntity, $itemData, $excludedFields);
    }

    /**
     * @param object $entity
     * @return null|object
     */
    protected function validateAndUpdateContext($entity)
    {
        // validate entity
        $validationErrors = $this->strategyHelper->validateEntity($entity, ['FieldConfigModel']);
        if ($validationErrors) {
            $this->context->incrementErrorEntriesCount();
            $this->strategyHelper->addValidationErrors($validationErrors, $this->context);

            return null;
        }

        $this->updateContextCounters($entity);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function updateContextCounters($entity)
    {
        // increment context counter
        $identifier = $this->databaseHelper->getIdentifier($entity);
        if ($identifier) {
            $this->context->incrementUpdateCount();
        } else {
            $this->context->incrementAddCount();
        }

        return $entity;
    }
}
