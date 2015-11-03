<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Strategy;

use Symfony\Component\Translation\TranslatorInterface;

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

        if (!in_array($entity->getType(), $supportedTypes, true)) {
            $this->addErrors('oro.entity_config.import.message.invalid_field_type');

            $entity = null;
        } else {
            $existingEntity = $this->findExistingEntity($entity);

            if ($existingEntity) {
                if ($existingEntity && $entity->getType() !== $existingEntity->getType()) {
                    $this->addErrors('oro.entity_config.import.message.change_type_not_allowed');

                    $entity = null;
                } elseif ($this->isSystemField($existingEntity)) {
                    $entity = null;
                }
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
            $this->updateContextCounters($entity);
        }

        return $errors ? null : $entity;
    }

    /**
     * @param string|array $errors
     */
    protected function addErrors($errors)
    {
        $errors = array_map(
            function ($error) {
                return $this->translator->trans($error);
            },
            (array)$errors
        );

        $this->context->incrementErrorEntriesCount();
        $this->strategyHelper->addValidationErrors($errors, $this->context);
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
                        $this->getFieldConstraints($config['constraints'])
                    );

                    if ($result) {
                        $errors[] = sprintf('%s.%s: %s', $scope, $code, implode(' ', $result));
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @param array $constraints
     * @return array
     */
    protected function getFieldConstraints(array $constraints)
    {
        $constraintObjects = [];
        foreach ($constraints as $constraint) {
            foreach ($constraint as $name => $options) {
                $constraintObjects[] = $this->constraintFactory->create($name, $options);
            }
        }

        return $constraintObjects;
    }
}
