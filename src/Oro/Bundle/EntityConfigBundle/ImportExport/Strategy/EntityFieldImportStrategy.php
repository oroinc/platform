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
        $existingEntity = $this->findExistingEntity($entity);

        if ($existingEntity) {
            if ($this->isSystemField($existingEntity)) {
                $entity = null;
            } elseif ($entity->getType() !== $existingEntity->getType()) {
                $this->context->incrementErrorEntriesCount();
                $this->strategyHelper->addValidationErrors(
                    [$this->translator->trans('oro.entity_config.import.message.change_type_not_allowed')],
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
        $this->validateEntityFields($entity);

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

    /**
     * @param FieldConfigModel $entity
     * @return null|FieldConfigModel
     */
    public function validateEntityFields(FieldConfigModel $entity)
    {
        $success = true;

        $fieldProperties = $this->fieldTypeProvider->getFieldProperties($entity->getType());

        foreach ($fieldProperties as $scope => $properties) {
            $scopeData = $entity->toArray($scope);

            foreach ($properties as $code => $config) {
                $success = $success && $this->validateScopeField($config, $scope, $code, $scopeData);
            }
        }

        return $success ? $entity : null;
    }

    /**
     * @param array $config
     * @param string $scope
     * @param string $code
     * @param array $scopeData
     * @return boolean
     */
    protected function validateScopeField(array $config, $scope, $code, array $scopeData)
    {
        if (!isset($scopeData[$code])) {
            return true;
        }

        $success = true;

        if ($scope === 'enum') {
            foreach ($scopeData[$code] as $key => $enumFields) {
                $enumEntity = $this->getEnumEntity($enumFields);

                $success = $success && $this->validateEntityField($enumEntity, $scope, $code . '.' . $key);
            }
        } elseif (isset($config['constraints'])) {
            $constraints = $this->getFieldConstraints($config['constraints']);

            $success = $success && $this->validateEntityField($scopeData[$code], $scope, $code, $constraints);
        }

        return $success;
    }

    /**
     * @param mixed $entity
     * @param string $scope
     * @param sring $code
     * @param array $constraints
     * @return boolean
     */
    protected function validateEntityField($entity, $scope, $code, array $constraints = null)
    {
        $errors = $this->strategyHelper->validateEntity($entity, $constraints);

        if ($errors) {
            $errorPrefix = $this->translator->trans(
                'oro.importexport.import.error %number%',
                [
                    '%number%' => $this->context->getReadOffset()
                ]
            );

            $this->context->incrementErrorEntriesCount();
            $this->strategyHelper->addValidationErrors(
                $errors,
                $this->context,
                sprintf('%s "%s.%s"', $errorPrefix, $scope, $code)
            );

            return false;
        }

        return true;
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

    /**
     * @param array $data
     * @return EnumValue
     */
    protected function getEnumEntity(array $data)
    {
        $enumEntity = new EnumValue();
        $enumEntity->fromArray($data);

        return $enumEntity;
    }
}
