<?php

namespace Oro\Bundle\LocaleBundle\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;

class DefaultFallbackGeneratorExtension extends AbstractEntityGeneratorExtension
{
    const DEFAULT_GETTER_PREFIX = 'getDefault';

    /**
     * @var array Array cotaining the classes and the fields which are configured to be extended
     * with default getter
     */
    protected $methodExtensions = [];

    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        if (!isset($schema['class'])) {
            return false;
        }

        return isset($this->methodExtensions[$schema['class']]);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, PhpClass $class)
    {
        if (!isset($schema['class']) || !isset($this->methodExtensions[$schema['class']])) {
            return false;
        }

        $fields = $this->methodExtensions[$schema['class']];

        if (empty($fields)) {
            return;
        }

        foreach ($fields as $getterName => $fieldName) {
            $this->generateDefaultGetter($getterName, $fieldName, $class);
        }
    }

    /**
     * Add class name and fields to data structure which contains the name of the classes and their fields,
     * which will be extended
     *
     * @param string $className
     * @param array $fields
     */
    public function addMethodExtension($className, array $fields)
    {
        if (isset($this->methodExtensions[$className])) {
            $this->methodExtensions[$className] = array_merge($this->methodExtensions[$className], $fields);
        } else {
            $this->methodExtensions[$className] = $fields;
        }
    }

    /**
     * Generate the code for default getter method for the received class and field name
     *
     * @param string $getterName
     * @param string $fieldName
     * @param PhpClass $class
     */
    protected function generateDefaultGetter($getterName, $fieldName, PhpClass $class)
    {
        $methodBody = [
            '$values = $this->'. $fieldName . '->filter(function (\Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue $value) {',
            '   return null === $value->getLocalization();',
            '});',
            'if ($values->count() > 1) {',
            '   throw new \LogicException(\'There must be only one default localized fallback value\');',
            '} elseif ($values->count() === 1) {',
            '   return $values->first();',
            '}',
            'return null;'
        ];

        $method = $this->generateClassMethod($this->getDefaultGetterMethodName($getterName), implode("\n", $methodBody));
        $class->setMethod($method);
    }
    public function getDefaultName()
    {
        $names = $this->names->filter(function (LocalizedFallbackValue $name) {
            return null === $name->getLocale();
        });

        if ($names->count() > 1) {
            throw new \LogicException('There must be only one default name');
        } elseif ($names->count() === 1) {
            return $names->first();
        }

        return null;
    }
    /**
     * Generate the default getter method name for the given field name
     *
     * @param string $fieldName
     * @return string
     */
    protected function getDefaultGetterMethodName($fieldName)
    {
        return self::DEFAULT_GETTER_PREFIX . ucfirst(Inflector::camelize($fieldName));
    }
}
