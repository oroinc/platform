<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Strategy;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

class EntityFieldImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param FieldConfigModel $entity
     *
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        $existingEntity = parent::findExistingEntity($entity, $searchContext);

        if (!$existingEntity) {
            $existingEntity = $this->databaseHelper->findOneBy(
                'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel',
                ['fieldName' => $entity->getFieldName(), 'entity' => $entity->getEntity()]
            );
        }

        return $existingEntity;
    }

    /**
     * @param FieldConfigModel $entity
     * @param FieldConfigModel $existingEntity
     *
     * {@inheritdoc}
     */
    protected function importExistingEntity($entity, $existingEntity, $itemData = null, array $excludedFields = [])
    {
        if ($this->isSystemField($existingEntity)) {
            return null;
        }

        if ($entity->getType() !== $existingEntity->getType()) {
            $this->context->incrementErrorEntriesCount();
            $this->strategyHelper->addValidationErrors(
                [$this->translator->trans('oro.entity_config.importexport.message.invelid_field_type')],
                $this->context
            );

            return null;
        }

        $entity->fromArray(
            'extend',
            array_merge($existingEntity->toArray('extend'), ['state' => ExtendScope::STATE_UPDATE]),
            []
        );

        /** @var ConfigManager $configManager */
        parent::importExistingEntity(
            $entity,
            $existingEntity,
            $itemData,
            array_merge($excludedFields, ['fieldName', 'type', 'entity'])
        );
    }

    /**
     * {@inheritdoc}
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

    /**
     * @param FieldConfigModel $entity
     * @return bool
     */
    protected function isSystemField(FieldConfigModel $entity)
    {
        $extend = $entity->toArray('extend');

        return $extend['owner'] === ExtendScope::OWNER_SYSTEM;
    }
}
