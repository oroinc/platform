<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions;

use Doctrine\Common\Util\Inflector;

use CG\Generator\PhpClass;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * This class provides PHP code generation logic for entities with associations.
 */
abstract class AbstractAssociationEntityGeneratorExtension extends AbstractEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        if (empty($schema['relation']) || empty($schema['relationData'])) {
            return false;
        }

        $result = false;
        foreach ($schema['relationData'] as $relationData) {
            if ($this->isSupportedRelation($relationData)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, PhpClass $class)
    {
        $this->generateAssociationMethods($schema, $class);
    }

    /**
     * Gets the kind of the association. For example 'activity', 'sponsorship' etc
     *
     * @return string|null The association kind or NULL for unclassified (default) association
     */
    protected function getAssociationKind()
    {
        return null;
    }

    /**
     * Gets the type of the association. For example manyToOne or manyToMany
     *
     * @return string
     */
    protected function getAssociationType()
    {
        return 'manyToOne';
    }

    /**
     * @param array $relationData
     *
     * @return bool
     */
    protected function isSupportedRelation(array $relationData)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $relationData['field_id'];

        return
            $fieldConfigId instanceof FieldConfigId
            && $fieldConfigId->getFieldType() === $this->getAssociationType()
            && $fieldConfigId->getFieldName() === ExtendHelper::buildAssociationName(
                $relationData['target_entity'],
                $this->getAssociationKind()
            );
    }

    /**
     * @param array    $schema
     * @param PhpClass $class
     *
     * @throws \RuntimeException If PHP code cannot be generated
     */
    protected function generateAssociationMethods(array $schema, PhpClass $class)
    {
        switch ($this->getAssociationType()) {
            case 'manyToOne':
                $this->generateManyToOneAssociationMethods($schema, $class);
                break;
            case 'manyToMany':
                $this->generateManyToManyAssociationMethods($schema, $class);
                break;
            default:
                throw new \RuntimeException(
                    sprintf('The "%s" association is not supported.', $this->getAssociationType())
                );
        }
    }

    /**
     * @param array    $schema
     * @param PhpClass $class
     */
    protected function generateManyToOneAssociationMethods(array $schema, PhpClass $class)
    {
        $prefix = $this->getAssociationKind();
        $prefix = Inflector::classify(null === $prefix ? '' : $prefix);

        $getMethodName   = sprintf('get%sTarget', $prefix);
        $setMethodName   = sprintf('set%sTarget', $prefix);
        $resetMethodName = sprintf('reset%sTargets', $prefix);

        $getMethodBody   = [];
        $setMethodBody   = [
            'if (null === $target) { $this->' . $resetMethodName . '(); return $this; }',
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);',
            '// This entity can be associated with only one another entity',
        ];
        $resetMethodBody = [];

        foreach ($schema['relationData'] as $relationData) {
            if (!$this->isSupportedRelation($relationData)) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId   = $relationData['field_id'];
            $fieldName       = $fieldConfigId->getFieldName();
            $targetClassName = $relationData['target_entity'];

            $getMethodBody[]   = sprintf(
                'if (null !== $this->%s) { return $this->%s; }',
                $fieldName,
                $fieldName
            );
            $setMethodBody[]   = sprintf(
                'if ($className === \'%s\') { $this->' . $resetMethodName . '(); $this->%s = %s; return $this; }',
                $targetClassName,
                $fieldName,
                '$target'
            );
            $resetMethodBody[] = sprintf(
                '$this->%s = null;',
                $fieldName
            );
        }

        $getMethodBody[] = 'return null;';
        $setMethodBody[] = 'throw new \RuntimeException(sprintf('
            . '\'The association with "%s" entity was not configured.\', $className));';

        $getMethodDocblock = "/**\n"
            . " * Gets the entity this entity is associated with\n"
            . " *\n"
            . " * @return object|null Any configurable entity\n"
            . " */";
        $setMethodDocblock = "/**\n"
            . " * Sets the entity this entity is associated with\n"
            . " *\n"
            . " * @param object \$target Any configurable entity that can be associated with this type of entity\n"
            . " * @return object This object\n"
            . " */";

        $class
            ->setMethod(
                $this
                    ->generateClassMethod($resetMethodName, implode("\n", $resetMethodBody))
                    ->setVisibility('private')
            )
            ->setMethod(
                $this
                    ->generateClassMethod($getMethodName, implode("\n", $getMethodBody))
                    ->setDocblock($getMethodDocblock)
            )
            ->setMethod(
                $this
                    ->generateClassMethod($setMethodName, implode("\n", $setMethodBody), ['target'])
                    ->setDocblock($setMethodDocblock)
            );
    }

    /**
     * @param array    $schema
     * @param PhpClass $class
     */
    public function generateManyToManyAssociationMethods(array $schema, PhpClass $class)
    {
        $prefix = $this->getAssociationKind();
        $prefix = Inflector::classify(null === $prefix ? '' : $prefix);

        $getMethodName    = sprintf('get%sTargets', $prefix);
        $addMethodName    = sprintf('add%sTarget', $prefix);
        $removeMethodName = sprintf('remove%sTarget', $prefix);

        $getMethodBody    = [
            '$className = \Doctrine\Common\Util\ClassUtils::getRealClass($targetClass);'
        ];
        $addMethodBody    = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);'
        ];
        $removeMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);'
        ];

        foreach ($schema['relationData'] as $relationData) {
            if (!$this->isSupportedRelation($relationData)) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId   = $relationData['field_id'];
            $fieldName       = $fieldConfigId->getFieldName();
            $targetClassName = $relationData['target_entity'];

            $getMethodBody[]    = sprintf(
                'if ($className === \'%s\') { return $this->%s; }',
                $targetClassName,
                $fieldName
            );
            $addMethodBody[]    = str_replace(
                ['{class}', '{field}'],
                [$targetClassName, $fieldName],
                "if (\$className === '{class}') {\n"
                . "    if (!\$this->{field}->contains(\$target)) { \$this->{field}->add(\$target); }\n"
                . "    return \$this;\n}"
            );
            $removeMethodBody[] = str_replace(
                ['{class}', '{field}'],
                [$targetClassName, $fieldName],
                "if (\$className === '{class}') {\n"
                . "    if (\$this->{field}->contains(\$target)) { \$this->{field}->removeElement(\$target); }\n"
                . "    return \$this;\n}"
            );
        }

        $throwStmt = 'throw new \RuntimeException('
            . 'sprintf(\'The association with "%s" entity was not configured.\', $className));';

        $getMethodBody[]    = $throwStmt;
        $addMethodBody[]    = $throwStmt;
        $removeMethodBody[] = $throwStmt;

        $getMethodDocblock    = "/**\n"
            . " * Gets entities of the given type associated with this entity\n"
            . " *\n"
            . " * @param string \$targetClass The class name of the target entity\n"
            . " * @return object[]\n"
            . " */";
        $addMethodDocblock    = "/**\n"
            . " * Associates the given entity with this entity\n"
            . " *\n"
            . " * @param object \$target Any configurable entity that can be associated with this type of entity\n"
            . " * @return object This object\n"
            . " */";
        $removeMethodDocblock = "/**\n"
            . " * Removes the association of the given entity and this entity\n"
            . " *\n"
            . " * @param object \$target Any configurable entity that can be associated with this type of entity\n"
            . " * @return object This object\n"
            . " */";

        $class
            ->setMethod(
                $this
                    ->generateClassMethod($getMethodName, implode("\n", $getMethodBody), ['targetClass'])
                    ->setDocblock($getMethodDocblock)
            )
            ->setMethod(
                $this
                    ->generateClassMethod($addMethodName, implode("\n", $addMethodBody), ['target'])
                    ->setDocblock($addMethodDocblock)
            )
            ->setMethod(
                $this
                    ->generateClassMethod($removeMethodName, implode("\n", $removeMethodBody), ['target'])
                    ->setDocblock($removeMethodDocblock)
            );
    }
}
