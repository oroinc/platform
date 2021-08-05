<?php

namespace Oro\Bundle\EntityConfigBundle\Validator;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;

/**
 * Implements base logic to create entity field config validation metadata loader
 */
abstract class AbstractFieldConfigBasedValidationLoader extends AbstractLoader
{
    /** @var array assoc array [fieldType => [constraint, ...], ...] */
    protected $constraintsMapping;

    /** @var ConfigProvider */
    protected $fieldConfigProvider;

    /** @var array */
    protected $constraintsByType;

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
        if (!$this->isClassApplicable($metadata)) {
            return false;
        }

        if (empty($this->constraintsMapping) || !$this->hasEntityConfig($metadata)) {
            return false;
        }

        $className = $metadata->getClassName();
        $fieldsConfig = $this->fieldConfigProvider->getConfigs($className, true);
        foreach ($fieldsConfig as $fieldConfig) {
            $this->processFieldConfig($metadata, $fieldConfig);
        }

        return true;
    }

    /**
     * @param ClassMetadata   $metadata
     * @param ConfigInterface $fieldConfig
     * @return mixed
     */
    abstract protected function processFieldConfig(ClassMetadata $metadata, ConfigInterface $fieldConfig);

    /**
     * @param string $type
     *
     * @return Constraint[]
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

    /**
     * Ignores proxy classes. Basically, all properties of the validating entities are proxy classes,
     * but we can securely ignore them because we receive also real entity classes.
     * see Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory::mergeConstraints
     */
    protected function isClassApplicable(ClassMetadata $metadata): bool
    {
        return $metadata->getClassName() === ClassUtils::getRealClass($metadata->getClassName());
    }

    private function hasEntityConfig(ClassMetadata $metadata): bool
    {
        // do preliminary checks to avoid unneeded calls of hasConfig() method
        $refl = $metadata->getReflectionClass();
        if ($refl->isInterface() || $refl->isAbstract()) {
            return false;
        }

        return $this->fieldConfigProvider->hasConfig($metadata->getClassName());
    }
}
