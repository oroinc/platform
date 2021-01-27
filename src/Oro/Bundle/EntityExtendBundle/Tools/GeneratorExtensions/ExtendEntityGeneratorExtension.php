<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Component\PhpUtils\ClassGenerator;
use Symfony\Component\Inflector\Inflector as SymfonyInflector;

/**
 * The main extension of the entity generator. This extension is responsible for generation of an extend entity skeleton
 * and all extend fields and relations.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExtendEntityGeneratorExtension extends AbstractEntityGeneratorExtension
{
    private Inflector $inflector;

    public function __construct(Inflector $inflector)
    {
        $this->inflector = $inflector;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supports(array $schema): bool
    {
        return true;
    }

    public function generate(array $schema, ClassGenerator $class): void
    {
        if (!empty($schema['inherit'])) {
            $class->addExtend($schema['inherit']);
        } elseif ('Custom' === $schema['type']) {
            // generate 'id' property and '__toString' method only for Custom entity without inheritance
            $class->addProperty('id')->setProtected();
            $class->addMethod('getId')->addBody('return $this->id;');

            $this->generateToStringMethod($schema, $class);
        }

        $this->generateConstructor($schema, $class);
        $class->addImplement(ExtendEntityInterface::class);

        $this->generateProperties('property', $schema, $class);
        $this->generateProperties('relation', $schema, $class);
        $this->generateProperties('default', $schema, $class);
        $this->generateCollectionMethods($schema, $class);
    }

    /**
     * @throws \ReflectionException
     */
    protected function generateConstructor(array $schema, ClassGenerator $class): void
    {
        $parentConstructorParams = [];
        $constructorBody = [];
        if (!empty($schema['inherit'])) {
            $parent = new \ReflectionClass($schema['inherit']);
            $parentConstructor = $parent->getConstructor();
            if ($parentConstructor) {
                $parentConstructorParams = $parentConstructor->getParameters();
                $callParamsDef = \array_map(static fn ($p) => '$' . $p->getName(), $parentConstructorParams);
                $constructorBody[] = \sprintf('parent::__construct(%s);', \implode(', ', $callParamsDef));
            }
        }
        foreach ($schema['addremove'] as $fieldName => $config) {
            $constructorBody[] = '$this->' . $fieldName . ' = new \Doctrine\Common\Collections\ArrayCollection();';
        }
        $constructor = $class->addMethod('__construct');
        $constructor->addBody(\implode("\n", $constructorBody));
        foreach ($parentConstructorParams as $refParam) {
            $param = $constructor->addParameter($refParam->getName());
            $param->setReference($refParam->isPassedByReference());
            if ($refParam->isDefaultValueAvailable()) {
                $param->setDefaultValue($refParam->getDefaultValue());
            }
            if ($type = $refParam->getType()) {
                /** @noinspection PhpPossiblePolymorphicInvocationInspection */
                $param->setType($type->getName());
            }
        }
    }

    protected function generateToStringMethod(array $schema, ClassGenerator $class): void
    {
        $toString = [];
        foreach ($schema['property'] as $fieldName => $config) {
            $isPrivate = is_array($config) && isset($config['private']) && $config['private'];
            if (!$isPrivate && 'string' === $schema['doctrine'][$schema['entity']]['fields'][$fieldName]['type']) {
                $toString[] = '$this->' . $this->generateGetMethodName($fieldName) . '()';
            }
        }

        $toStringBody = empty($toString)
            ? 'return (string) $this->getId();'
            : 'return (string)' . implode(' . " " . ', $toString) . ';';
        $class->addMethod('__toString')->addBody($toStringBody);
    }

    protected function generateProperties(string $propertyType, array $schema, ClassGenerator $class): void
    {
        foreach ($schema[$propertyType] as $fieldName => $config) {
            if (('relation' === $propertyType) && !$this->isSupportedRelation($schema, $fieldName)) {
                continue;
            }

            $property = $class->addProperty($fieldName);
            $property->setProtected();
            $entity = $schema['entity'] ?? null;
            if ($entity) {
                $default = $schema['doctrine'][$entity]['fields'][$fieldName]['default'] ?? null;
                if (null !== $default) {
                    $property->setValue($default);
                }
            }
            $isPrivate = is_array($config) && isset($config['private']) && $config['private'];
            if (!$isPrivate) {
                $class->addMethod($this->generateGetMethodName($fieldName))
                    ->addBody('return $this->' . $fieldName . ';');
                $class->addMethod($this->generateSetMethodName($fieldName))
                    ->addBody($this->getSetterBody($fieldName, $schema))
                    ->addParameter('value');
            }
        }
    }

    protected function isSupportedRelation(array $schema, string $fieldName): bool
    {
        $isSupportedRelation = true;

        if (isset($schema['relationData'])) {
            foreach ($schema['relationData'] as $relationData) {
                /** @var FieldConfigId $fieldId */
                $fieldId = $relationData['field_id'];
                if ($fieldId instanceof FieldConfigId && $fieldId->getFieldName() === $fieldName) {
                    $isSupportedRelation = !in_array(
                        $relationData['state'],
                        [ExtendScope::STATE_NEW, ExtendScope::STATE_DELETE],
                        true
                    );
                    break;
                }
            }
        }

        return $isSupportedRelation;
    }

    protected function getSetterBody(string $fieldName, array $schema): string
    {
        if (!isset($schema['addremove'][$fieldName])) {
            $type = null;
            $default = null;
            $entity = $schema['entity'] ?? null;
            if ($entity) {
                $type = $schema['doctrine'][$entity]['fields'][$fieldName]['type'] ?? null;
                $default = $schema['doctrine'][$schema['entity']]['fields'][$fieldName]['default'] ?? null;
            }
            if ('boolean' === $type && null !== $default) {
                return '$this->' . $fieldName . ' = (bool)$value; return $this;';
            }
            return '$this->' . $fieldName . ' = $value; return $this;';
        }

        $relationFieldName = $schema['addremove'][$fieldName]['self'];
        $addMethodName = $this->generateAddMethodName($relationFieldName);
        $removeMethodName = $this->generateRemoveMethodName($relationFieldName);
        return <<<METHOD_BODY
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
    }

    protected function generateCollectionMethods(array $schema, ClassGenerator $class): void
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

            $class->addMethod($this->generateAddMethodName($config['self']))
                ->addBody(\implode("\n", $addMethodBody))
                ->addParameter('value');
            $class->addMethod($this->generateRemoveMethodName($config['self']))
                ->addBody(\implode("\n", $removeMethodBody))
                ->addParameter('value');
        }
    }

    protected function generateGetMethodName(string $fieldName): string
    {
        return 'get' . \ucfirst($this->inflector->camelize($fieldName));
    }

    protected function generateSetMethodName(string $fieldName): string
    {
        return 'set' . \ucfirst($this->inflector->camelize($fieldName));
    }

    protected function generateAddMethodName(string $fieldName): string
    {
        return 'add' . \ucfirst($this->getSingular($fieldName));
    }

    protected function generateRemoveMethodName(string $fieldName): string
    {
        return 'remove' . \ucfirst($this->getSingular($fieldName));
    }

    protected function getSingular(string $fieldName): string
    {
        $singular = SymfonyInflector::singularize($this->inflector->classify($fieldName));
        if (\is_array($singular)) {
            $singular = \reset($singular);
        }

        return $singular;
    }
}
