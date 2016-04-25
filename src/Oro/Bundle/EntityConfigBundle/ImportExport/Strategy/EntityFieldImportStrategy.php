<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Strategy;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Model\EnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Oro\Bundle\ImportExportBundle\Strategy\Import\AbstractImportStrategy;

class EntityFieldImportStrategy extends AbstractImportStrategy
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConstraintFactory */
    protected $constraintFactory;

    /** @var FieldTypeProvider */
    protected $fieldTypeProvider;

    /** @var bool */
    protected $isExistingEntity = false;

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param ConstraintFactory $constraintFactory
     */
    public function setConstraintFactory(ConstraintFactory $constraintFactory)
    {
        $this->constraintFactory = $constraintFactory;
    }

    /**
     * @param FieldTypeProvider $fieldTypeProvider
     */
    public function setFieldTypeProvider(FieldTypeProvider $fieldTypeProvider)
    {
        $this->fieldTypeProvider = $fieldTypeProvider;
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
        $supportedTypes = $this->fieldTypeProvider->getSupportedFieldTypes();

        if ((string)$entity->getFieldName() === '') {
            $this->addErrors($this->translator->trans('oro.entity_config.import.message.invalid_field_name'));

            return null;
        }

        if (!in_array($entity->getType(), $supportedTypes, true)) {
            $this->addErrors($this->translator->trans('oro.entity_config.import.message.invalid_field_type'));

            return null;
        }

        $existingEntity = $this->findExistingEntity($entity);
        $this->isExistingEntity = (bool)$existingEntity;
        if ($this->isExistingEntity) {
            if ($entity->getType() !== $existingEntity->getType()) {
                $this->addErrors($this->translator->trans('oro.entity_config.import.message.change_type_not_allowed'));

                return null;
            }
            if ($this->isSystemField($existingEntity)) {
                return null;
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
            $this->entityName,
            ['fieldName' => $entity->getFieldName(), 'entity' => $entity->getEntity()]
        );
    }

    /**
     * @param FieldConfigModel $entity
     * @return null|FieldConfigModel
     */
    protected function validateAndUpdateContext(FieldConfigModel $entity)
    {
        $errors = array_merge(
            (array)$this->strategyHelper->validateEntity($entity, ['FieldConfigModel']),
            $this->validateEntityFields($entity)
        );

        if ($errors) {
            $this->addErrors($errors);
        } else {
            $this->updateContextCounters();
        }

        return $errors ? null : $entity;
    }

    /**
     * @param string|array $errors
     */
    protected function addErrors($errors)
    {
        $this->context->incrementErrorEntriesCount();
        $this->strategyHelper->addValidationErrors((array)$errors, $this->context);
    }

    protected function updateContextCounters()
    {
        if ($this->isExistingEntity) {
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
        return isset($extend['owner']) && $extend['owner'] === ExtendScope::OWNER_SYSTEM;
    }

    /**
     * @param FieldConfigModel $entity
     * @return array
     */
    protected function validateEntityFields(FieldConfigModel $entity)
    {
        $errors = [];
        $fieldProperties = $this->fieldTypeProvider->getFieldProperties($entity->getType());

        foreach ($fieldProperties as $scope => $properties) {
            $scopeData = $entity->toArray($scope);

            foreach ($properties as $code => $config) {
                if (!isset($scopeData[$code])) {
                    continue;
                }

                if ($scope === 'enum') {
                    foreach ($scopeData[$code] as $key => $enumFields) {
                        $result = $this->strategyHelper->validateEntity(EnumValue::createFromArray($enumFields));
                        if ($result) {
                            $errors[] = sprintf('%s.%s.%s: %s', $scope, $code, $key, implode(' ', $result));
                        }
                    }
                } elseif (isset($config['constraints'])) {
                    $result = $this->strategyHelper->validateEntity(
                        $scopeData[$code],
                        $this->constraintFactory->parse($config['constraints'])
                    );

                    if ($result) {
                        $errors[] = sprintf('%s.%s: %s', $scope, $code, implode(' ', $result));
                    }
                }
            }
        }

        return $errors;
    }
}
