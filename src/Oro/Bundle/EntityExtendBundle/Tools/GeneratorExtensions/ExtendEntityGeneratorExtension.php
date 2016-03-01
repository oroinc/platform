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
        $class->addInterfaceName('Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface');

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
            $isPrivate = is_array($config) && isset($config['private']) && $config['private'];
            if (!$isPrivate && $schema['doctrine'][$schema['entity']]['fields'][$fieldName]['type'] === 'string') {
                $toString[] = '$this->' . $this->generateGetMethodName($fieldName) . '()';
            }
        }

        $toStringBody = empty($toString)
            ? 'return (string) $this->getId();'
            : 'return (string)' . implode(' . ', $toString) . ';';
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
            $class->setProperty(PhpProperty::create($fieldName)->setVisibility('protected'));

            $isPrivate = is_array($config) && isset($config['private']) && $config['private'];
            if (!$isPrivate) {
                $class
                    ->setMethod(
                        $this->generateClassMethod(
                            $this->generateGetMethodName($fieldName),
                            'return $this->' . $fieldName . ';'
                        )
                    )
                    ->setMethod(
                        $this->generateClassMethod(
                            $this->generateSetMethodName($fieldName),
                            $this->getSetterBody($fieldName, $schema),
                            ['value']
                        )
                    );
            }
        }
    }

    /**
     * @param string $fieldName
     * @param array $schema
     * @return string
     */
    protected function getSetterBody($fieldName, array $schema)
    {
        if (!isset($schema['addremove'][$fieldName])) {
            return '$this->' . $fieldName . ' = $value; return $this;';
        } else {
            $addMethodName = $this->generateAddMethodName($fieldName);
            $removeMethodName = $this->generateRemoveMethodName($fieldName);
            $body = <<<METHOD_BODY
if ((!\$value instanceof \Traversable && !is_array(\$value) && !\$value instanceof \ArrayAccess) ||
    !\$this->$fieldName instanceof \Doctrine\Common\Collections\Collection) {
    \$this->$fieldName = \$value;
    return \$this;
}
foreach (\$this->$fieldName as \$item) {
    \$this->$removeMethodName(\$item);
}
foreach (\$value as \$item) {
    \$this->$addMethodName(\$item);
}
return \$this;
METHOD_BODY;
            return $body;
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
                if ($config['is_target_addremove']) {
                    $addMethodBody[] = "    \$value->{$this->generateAddMethodName($config['target'])}(\$this);";
                    $removeMethodBody[] = "    \$value->{$this->generateRemoveMethodName($config['target'])}(\$this);";
                } else {
                    $addMethodBody[] = "    \$value->{$this->generateSetMethodName($config['target'])}(\$this);";
                    $removeMethodBody[] = "    \$value->{$this->generateSetMethodName($config['target'])}(null);";
                }
            }
            $addMethodBody[]    = '}';
            $removeMethodBody[] = '}';

            $class
                ->setMethod(
                    $this->generateClassMethod(
                        $this->generateAddMethodName($config['self']),
                        implode("\n", $addMethodBody),
                        ['value']
                    )
                )
                ->setMethod(
                    $this->generateClassMethod(
                        $this->generateRemoveMethodName($config['self']),
                        implode("\n", $removeMethodBody),
                        ['value']
                    )
                );
        }
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function generateGetMethodName($fieldName)
    {
        return 'get' . ucfirst(Inflector::camelize($fieldName));
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function generateSetMethodName($fieldName)
    {
        return 'set' . ucfirst(Inflector::camelize($fieldName));
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function generateAddMethodName($fieldName)
    {
        return 'add' . ucfirst(Inflector::camelize($fieldName));
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function generateRemoveMethodName($fieldName)
    {
        return 'remove' . ucfirst(Inflector::camelize($fieldName));
    }
}
