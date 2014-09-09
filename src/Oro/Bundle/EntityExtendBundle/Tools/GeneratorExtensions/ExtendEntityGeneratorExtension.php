<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;
use CG\Generator\PhpParameter;
use CG\Generator\PhpProperty;

use Doctrine\Common\Inflector\Inflector;

/**
 * The main extension of the entity generator. This extension is responsible for generate extend entity skeleton
 * and all extend fields and relations.
 */
class ExtendEntityGeneratorExtension extends AbstractEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, PhpClass $class)
    {
        if (!empty($schema['inherit'])) {
            $class->setParentClassName($schema['inherit']);
        } elseif ($schema['type'] === 'Custom') {
            // generate 'id' property and '__toString' method only for Custom entity without inheritance
            $class->setProperty(PhpProperty::create('id')->setVisibility('protected'));
            $class->setMethod($this->generateClassMethod('getId', 'return $this->id;'));

            $this->generateToStringMethod($schema, $class);
        }

        $this->generateConstructor($schema, $class);
        $class->setInterfaceNames(['Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface']);

        $this->generateProperties('property', $schema, $class);
        $this->generateProperties('relation', $schema, $class);
        $this->generateProperties('default', $schema, $class);
        $this->generateCollectionMethods($schema, $class);
    }

    /**
     * @param array    $schema
     * @param PhpClass $class
     */
    protected function generateConstructor(array $schema, PhpClass $class)
    {
        $constructorParams = [];
        $constructorBody = [];
        if (!empty($schema['inherit'])) {
            $parent = new \ReflectionClass($schema['inherit']);
            $parentConstructor = $parent->getConstructor();
            if ($parentConstructor) {
                $params        = $parentConstructor->getParameters();
                $callParamsDef = [];
                foreach ($params as $param) {
                    $constructorParams[] = PhpParameter::fromReflection($param);
                    $callParamsDef[] = '$' . $param->getName();
                }

                $constructorBody[] = sprintf('parent::__construct(%s);', implode(', ', $callParamsDef));
            }
        }
        foreach ($schema['addremove'] as $fieldName => $config) {
            $constructorBody[] = '$this->' . $fieldName . ' = new \Doctrine\Common\Collections\ArrayCollection();';
        }
        $constructor = $this->generateClassMethod('__construct', implode("\n", $constructorBody));
        foreach ($constructorParams as $constructorParam) {
            $constructor->addParameter($constructorParam);
        }
        $class->setMethod($constructor);
    }

    /**
     * TODO: custom entity instance as manyToOne relation find the way to show it on view
     * we should mark some field as title
     *
     * @param array    $schema
     * @param PhpClass $class
     */
    protected function generateToStringMethod(array $schema, PhpClass $class)
    {
        $toString = [];
        foreach ($schema['property'] as $fieldName => $config) {
            if ($schema['doctrine'][$schema['entity']]['fields'][$fieldName]['type'] == 'string') {
                $toString[] = '$this->get' . ucfirst(Inflector::camelize($fieldName)) . '()';
            }
        }

        $toStringBody = 'return (string) $this->getId();';
        if (count($toString) > 0) {
            $toStringBody = 'return (string)' . implode(' . ', $toString) . ';';
        }
        $class->setMethod($this->generateClassMethod('__toString', $toStringBody));
    }

    /**
     * @param string   $propertyType
     * @param array    $schema
     * @param PhpClass $class
     */
    protected function generateProperties($propertyType, array $schema, PhpClass $class)
    {
        foreach ($schema[$propertyType] as $fieldName => $config) {
            $class
                ->setProperty(PhpProperty::create($fieldName)->setVisibility('protected'))
                ->setMethod(
                    $this->generateClassMethod(
                        'get' . ucfirst(Inflector::camelize($fieldName)),
                        'return $this->' . $fieldName . ';'
                    )
                )
                ->setMethod(
                    $this->generateClassMethod(
                        'set' . ucfirst(Inflector::camelize($fieldName)),
                        '$this->' . $fieldName . ' = $value; return $this;',
                        ['value']
                    )
                );
        }
    }

    /**
     * @param array    $schema
     * @param PhpClass $class
     */
    protected function generateCollectionMethods(array $schema, PhpClass $class)
    {
        foreach ($schema['addremove'] as $fieldName => $config) {
            $addMethodBody    = [
                'if (!$this->' . $fieldName . '->contains($value)) {',
                '    $this->' . $fieldName . '->add($value);'
            ];
            $removeMethodBody = [
                'if ($this->' . $fieldName . ' && $this->' . $fieldName . '->contains($value)) {',
                '    $this->' . $fieldName . '->removeElement($value);',
            ];
            if (isset($config['target'])) {
                $addMethodBody[]    = '    $value->' . ($config['is_target_addremove'] ? 'add' : 'set')
                    . ucfirst(Inflector::camelize($config['target'])) . '($this);';
                $removeMethodBody[] = '    $value->' . ($config['is_target_addremove'] ? 'remove' : 'set')
                    . ucfirst(Inflector::camelize($config['target']))
                    . '(' . ($config['is_target_addremove'] ? '$this' : 'null') . ');';
            }
            $addMethodBody[]    = '}';
            $removeMethodBody[] = '}';

            $class
                ->setMethod(
                    $this->generateClassMethod(
                        'add' . ucfirst(Inflector::camelize($config['self'])),
                        implode("\n", $addMethodBody),
                        ['value']
                    )
                )
                ->setMethod(
                    $this->generateClassMethod(
                        'remove' . ucfirst(Inflector::camelize($config['self'])),
                        implode("\n", $removeMethodBody),
                        ['value']
                    )
                );
        }
    }
}
