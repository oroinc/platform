<?php

namespace Oro\Bundle\EntityExtendBundle\Validator;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class ExtendFieldValidationLoader extends AbstractLoader
{
    /** @var array assoc array [fieldType => contraints]*/
    protected $constraintsMapping;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ConfigProvider */
    protected $formConfigProvider;

    /** @var array */
    protected $constraintsByType;

    /**
     * @param ConfigProvider $extendConfigProvider
     * @param ConfigProvider $formConfigProvider
     */
    public function __construct(
        ConfigProvider $extendConfigProvider,
        ConfigProvider $formConfigProvider
    ) {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->formConfigProvider   = $formConfigProvider;
    }

    /**
     * @param string $fieldType
     * @param array  $constraintData
     */
    public function addConstraints($fieldType, $constraintData)
    {
        $this->constraintsMapping[$fieldType] = $constraintData;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        $className   = $metadata->getClassName();
        if (empty($this->constraintsMapping) || !$this->formConfigProvider->hasConfig($className)) {
            return false;
        }

        $formConfigs = $this->formConfigProvider->getConfigs($className);
        foreach ($formConfigs as $formConfig) {
            if (!$formConfig->is('is_enabled')) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $formConfig->getId();
            $fieldName     = $fieldConfigId->getFieldName();

            if (!$this->isApplicable($className, $fieldName)) {
                continue;
            }

            $constraints = $this->getConstraintsByFieldType($fieldConfigId->getFieldType());
            foreach ($constraints as $constraint) {
                $metadata->addPropertyConstraint($fieldName, $constraint);
            }
        }

        return true;
    }

    /**
     * Check if field applicable to add constraint
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isApplicable($className, $fieldName)
    {
        $extendConfig = $this->extendConfigProvider->getConfig($className, $fieldName);

        return !$extendConfig->is('is_deleted') &&
            !$extendConfig->is('state', ExtendScope::STATE_NEW) &&
            $extendConfig->is('is_extend');
    }

    /**
     * @param string $type
     *
     * @return array
     */
    protected function getConstraintsByFieldType($type)
    {
        if (empty($this->constraintsByType)) {
            foreach ($this->constraintsMapping as $fieldType => $constraints) {
                if (empty($constraints)) {
                    continue;
                }

                foreach ($this->parseNodes($constraints) as $constraint) {
                    $this->constraintsByType[$fieldType][] = $constraint;
                }
            }
        }

        return empty($this->constraintsByType[$type]) ? [] : $this->constraintsByType[$type];
    }

    /**
     * Parses a collection of YAML nodes
     *
     * @param array $nodes The YAML nodes
     *
     * @return array An array of values or Constraint instances
     */
    protected function parseNodes(array $nodes)
    {
        $constraints = [];

        foreach ($nodes as $name => $childNodes) {
            if (is_numeric($name) && is_array($childNodes) && count($childNodes) == 1) {
                $options = current($childNodes);

                if (is_array($options)) {
                    $options = $this->parseNodes($options);
                }

                $constraints[] = $this->newConstraint(key($childNodes), $options);
            } else {
                if (is_array($childNodes)) {
                    $childNodes = $this->parseNodes($childNodes);
                }

                $constraints[$name] = $childNodes;
            }
        }

        return $constraints;
    }
}
