<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Strategy;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Model\EnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Oro\Bundle\ImportExportBundle\Strategy\Import\AbstractImportStrategy;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;

class EntityFieldImportStrategy extends AbstractImportStrategy
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConstraintFactory */
    protected $constraintFactory;

    /** @var FieldTypeProvider */
    protected $fieldTypeProvider;

    /** @var FieldNameValidationHelper */
    protected $fieldValidationHelper;

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
     * @param FieldNameValidationHelper $fieldValidationHelper
     */
    public function setFieldValidationHelper(FieldNameValidationHelper $fieldValidationHelper)
    {
        $this->fieldValidationHelper = $fieldValidationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        /** @var FieldConfigModel $entity */
        $entity = $this->beforeProcessEntity($entity);
        if (!$entity) {
            return null;
        }
        $entity = $this->processEntity($entity);
        if (!$entity) {
            return null;
        }
        $entity = $this->afterProcessEntity($entity);
        if (!$entity) {
            return null;
        }
        return $this->validateAndUpdateContext($entity);
    }

    /**
     * @param FieldConfigModel $entity
     * @return null|FieldConfigModel
     */
    protected function processEntity(FieldConfigModel $entity)
    {
        $supportedTypes = $this->fieldTypeProvider->getSupportedFieldTypes();
        if (!in_array($entity->getType(), $supportedTypes, true)) {
            $this->addErrors($this->translator->trans('oro.entity_config.import.message.invalid_field_type'));

            return null;
        }
        return $entity;
    }

    /**
     * @param FieldConfigModel $entity
     * @return null|FieldConfigModel
     */
    protected function validateAndUpdateContext(FieldConfigModel $entity)
    {
        $errors = array_merge(
            (array)$this->strategyHelper->validateEntity(
                $entity,
                null,
                new GroupSequence(['FieldConfigModel', 'Sql', 'ChangeTypeField'])
            ),
            $this->validateEntityFields($entity)
        );

        if ($errors) {
            $this->addErrors($errors);
            return null;
        }

        $this->updateContextCounters($entity);
        $this->fieldValidationHelper->registerField($entity);

        return $entity;
    }

    /**
     * @param string|array $errors
     */
    protected function addErrors($errors)
    {
        $this->context->incrementErrorEntriesCount();
        $this->strategyHelper->addValidationErrors((array)$errors, $this->context);
    }

    /**
     * @param FieldConfigModel $entity
     */
    protected function updateContextCounters(FieldConfigModel $entity)
    {
        $fieldName = $entity->getFieldName();

        $existingField = $this->fieldValidationHelper->getSimilarExistingFieldData(
            $entity->getEntity()->getClassName(),
            $fieldName
        );

        if ($existingField && $fieldName === $existingField[0] && $entity->getType() === $existingField[1]) {
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
