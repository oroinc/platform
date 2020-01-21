<?php

namespace Oro\Bundle\LocaleBundle\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;
use CG\Generator\PhpParameter;
use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\ExtendFallback;

/**
 * Generates getters and setters for default fallback fields.
 */
class DefaultFallbackGeneratorExtension extends AbstractEntityGeneratorExtension
{
    /** @var array [class name => [singular field name => field name, ...], ...] */
    private $fieldMap;

    /**
     * @param array $fieldMap [class name => [singular field name => field name, ...], ...]
     */
    public function __construct(array $fieldMap)
    {
        $this->fieldMap = $fieldMap;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        return isset($schema['class'], $this->fieldMap[$schema['class']]);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, PhpClass $class)
    {
        if (!$this->supports($schema)) {
            return;
        }

        $fields = $this->fieldMap[$schema['class']];
        if (empty($fields)) {
            return;
        }

        $class->setParentClassName(ExtendFallback::class);
        $class->addUseStatement(Localization::class);
        $class->addUseStatement(LocalizedFallbackValue::class);

        foreach ($fields as $singularName => $fieldName) {
            $this->generateGetter($singularName, $fieldName, $class);
            $this->generateDefaultGetter($singularName, $fieldName, $class);
            $this->generateDefaultSetter($singularName, $fieldName, $class);
        }
    }

    /**
     * Generate the code for getter method
     *
     * @param string $singularName
     * @param string $fieldName
     * @param PhpClass $class
     */
    protected function generateGetter($singularName, $fieldName, PhpClass $class)
    {
        $getter = $this->getMethodName($singularName, 'get');
        $methodBody = sprintf('return $this->getFallbackValue($this->%s, $localization);', $fieldName);

        $localization = PhpParameter::create('localization')
            ->setType(Localization::class)
            ->setDefaultValue(null);

        $method = $this->generateClassMethod($getter, $methodBody);
        $method->setDocblock(
            $this->generateDocblock(
                ['Localization|null' =>'$localization'],
                'LocalizedFallbackValue|null'
            )
        );
        $method->setParameters([$localization]);
        $class->setMethod($method);
    }

    /**
     * Generate the code for default getter method
     *
     * @param string $singularName
     * @param string $fieldName
     * @param PhpClass $class
     */
    protected function generateDefaultGetter($singularName, $fieldName, PhpClass $class)
    {
        $defaultGetter = $this->getMethodName($singularName, 'getDefault');
        $methodBody = sprintf('return $this->getDefaultFallbackValue($this->%s);', $fieldName);

        $method = $this->generateClassMethod($defaultGetter, $methodBody);
        $method->setDocblock($this->generateDocblock([], 'LocalizedFallbackValue|null'));
        $class->setMethod($method);
    }

    /**
     * Generate the code for default setter method
     *
     * @param string $singularName
     * @param string $fieldName
     * @param PhpClass $class
     */
    protected function generateDefaultSetter($singularName, $fieldName, PhpClass $class)
    {
        $defaultSetter = $this->getMethodName($singularName, 'setDefault');

        $methodBody = sprintf('return $this->setDefaultFallbackValue($this->%s, $value);', $fieldName);

        $method = $this->generateClassMethod($defaultSetter, $methodBody);
        $method->setDocblock($this->generateDocblock(['string' =>  '$value'], '$this'));
        $method->setParameters([PhpParameter::create('value')]);

        $class->setMethod($method);
    }

    /**
     * @param array $params
     * @param string $return
     * @return string
     */
    protected function generateDocblock(array $params, $return = null)
    {
        $parts = ['/**'];

        foreach ($params as $type => $param) {
            $parts[] = sprintf(' * @param %s %s', $type, $param);
        }

        if ($return) {
            $parts[] = sprintf(' * @return %s', $return);
        }

        $parts[] = ' */';

        return implode("\n", $parts);
    }

    /**
     * @param string $fieldName
     * @param string $prefix
     * @return string
     */
    protected function getMethodName($fieldName, $prefix)
    {
        return $prefix . ucfirst(Inflector::camelize($fieldName));
    }
}
