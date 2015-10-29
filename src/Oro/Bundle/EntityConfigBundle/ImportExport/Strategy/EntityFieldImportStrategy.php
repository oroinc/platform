<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Strategy;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ImportExportBundle\Strategy\Import\AbstractImportStrategy;

class EntityFieldImportStrategy extends AbstractImportStrategy
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
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        /** @var FieldConfigModel $entity */
        $entity = $this->beforeProcessEntity($entity);
        $entity = $this->processEntity($entity);
        $entity = $this->afterProcessEntity($entity);
        if ($entity) {
            $entity = $this->validateAndUpdateContext($entity);
        }

        return $entity;
    }

    /**
     * @param FieldConfigModel $entity
     * @return null|FieldConfigModel
     */
    protected function processEntity(FieldConfigModel $entity)
    {
        $existingEntity = $this->findExistingEntity($entity);

        if ($existingEntity) {
            if ($this->isSystemField($existingEntity)) {
                $entity = null;
            } elseif ($entity->getType() !== $existingEntity->getType()) {
                $this->context->incrementErrorEntriesCount();
                $this->strategyHelper->addValidationErrors(
                    [$this->translator->trans('oro.entity_config.importexport.message.invelid_field_type')],
                    $this->context
                );

                $entity = null;
            }
        }

        return $entity;
    }

    /**
     * @param FieldConfigModel $entity
     * @param array $searchContext
     * @return null|FieldConfigModel
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        return $this->databaseHelper->findOneBy(
            'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel',
            ['fieldName' => $entity->getFieldName(), 'entity' => $entity->getEntity()]
        );
    }

    /**
     * @param FieldConfigModel $entity
     * @return null|FieldConfigModel
     */
    protected function validateAndUpdateContext(FieldConfigModel $entity)
    {
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
     * @param FieldConfigModel $entity
     */
    protected function updateContextCounters(FieldConfigModel $entity)
    {
        if ($this->findExistingEntity($entity)) {
            $this->context->incrementUpdateCount();
        } else {
            $this->context->incrementAddCount();
        }
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
