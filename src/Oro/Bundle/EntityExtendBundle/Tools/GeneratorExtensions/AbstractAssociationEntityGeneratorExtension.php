<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationNameGenerator;
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
     * {@see Oro\Bundle\EntityExtendBundle\Extend\RelationType}
     *
     * @return string
     */
    protected function getAssociationType()
    {
        return RelationType::MANY_TO_ONE;
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
            && (
                $fieldConfigId->getFieldType() === $this->getAssociationType()
                || (
                    $this->getAssociationType() === RelationType::MULTIPLE_MANY_TO_ONE
                    && RelationType::MANY_TO_ONE === $fieldConfigId->getFieldType()
                )
            )
            && $fieldConfigId->getFieldName() === ExtendHelper::buildAssociationName(
                $relationData['target_entity'],
                $this->getAssociationKind()
            )
            && !in_array($relationData['state'], [ExtendScope::STATE_NEW, ExtendScope::STATE_DELETE], true);
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
            case RelationType::MANY_TO_ONE:
                $this->generateManyToOneAssociationMethods($schema, $class);
                break;
            case RelationType::MANY_TO_MANY:
                $this->generateManyToManyAssociationMethods($schema, $class);
                break;
            case RelationType::MULTIPLE_MANY_TO_ONE:
                $this->generateMultipleManyToOneAssociationMethods($schema, $class);
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
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function generateMultipleManyToOneAssociationMethods(array $schema, PhpClass $class)
    {
        $associationKind = $this->getAssociationKind();

        $supportMethodName = AssociationNameGenerator::generateSupportTargetMethodName($associationKind);
        $hasMethodName = AssociationNameGenerator::generateHasTargetMethodName($associationKind);
        $getMethodName = AssociationNameGenerator::generateGetTargetsMethodName($associationKind);
        $addMethodName = AssociationNameGenerator::generateAddTargetMethodName($associationKind);
        $removeMethodName = AssociationNameGenerator::generateRemoveTargetMethodName($associationKind);

        $supportMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getRealClass($targetClass);',
        ];
        $getMethodBody = [
            '$targets = [];',
        ];
        $hasMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);'
        ];
        $addMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);',
        ];
        $removeMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);'
        ];

        foreach ($schema['relationData'] as $relationData) {
            if (!$this->isSupportedRelation($relationData)) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $relationData['field_id'];
            $fieldName = $fieldConfigId->getFieldName();
            $targetClassName = $relationData['target_entity'];

            $supportMethodBody[] = sprintf(
                'if ($className === \'%s\') { return true; }',
                $targetClassName
            );
            $getMethodBody[] = str_replace(
                ['{field}'],
                [$fieldName],
                "\$entity = \$this->{field};\n"
                . "if (\$entity) {\n"
                . "    \$targets[] = \$entity;\n"
                . "}"
            );
            $hasMethodBody[] = sprintf(
                'if ($className === \'%s\') { return $this->%s === $target; }',
                $targetClassName,
                $fieldName
            );
            $addMethodBody[] = sprintf(
                'if ($className === \'%s\') { $this->%s = $target; return $this; }',
                $targetClassName,
                $fieldName
            );
            $removeMethodBody[] = str_replace(
                ['{class}', '{field}'],
                [$targetClassName, $fieldName],
                "if (\$className === '{class}') {\n"
                . "    if (\$this->{field} === \$target) { \$this->{field} = null; }\n"
                . "    return \$this;\n}"
            );
        }

        $throwStmt = 'throw new \RuntimeException(sprintf('
            . '\'The association with "%s" entity was not configured.\', $className));';

        $supportMethodBody[] = 'return false;';
        $getMethodBody[] = "return \$targets;";
        $hasMethodBody[] = 'return false;';
        $addMethodBody[] = $throwStmt;
        $removeMethodBody[] = $throwStmt;

        $supportMethodDocblock = "/**\n"
            . " * Checks if this entity can be associated with the given target entity type\n"
            . " *\n"
            . " * @param string \$targetClass The class name of the target entity\n"
            . " * @return bool\n"
            . " */";
        $getMethodDocblock = "/**\n"
            . " * Gets the entities this entity is associated with\n"
            . " *\n"
            . " * @return object[]\n"
            . " */";
        $hasMethodDocblock = "/**\n"
            . " * Checks is the given entity is associated with this entity\n"
            . " *\n"
            . " * @param object \$target Any configurable entity that can be associated with this type of entity\n"
            . " * @return bool\n"
            . " */";
        $addMethodDocblock = "/**\n"
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
                    ->generateClassMethod($supportMethodName, implode("\n", $supportMethodBody), ['targetClass'])
                    ->setDocblock($supportMethodDocblock)
            )
            ->setMethod(
                $this
                    ->generateClassMethod($getMethodName, implode("\n", $getMethodBody))
                    ->setDocblock($getMethodDocblock)
            )
            ->setMethod(
                $this
                    ->generateClassMethod($hasMethodName, implode("\n", $hasMethodBody), ['target'])
                    ->setDocblock($hasMethodDocblock)
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

    /**
     * @param array    $schema
     * @param PhpClass $class
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function generateManyToOneAssociationMethods(array $schema, PhpClass $class)
    {
        $associationKind = $this->getAssociationKind();

        $supportMethodName = AssociationNameGenerator::generateSupportTargetMethodName($associationKind);
        $getMethodName = AssociationNameGenerator::generateGetTargetMethodName($associationKind);
        $setMethodName = AssociationNameGenerator::generateSetTargetMethodName($associationKind);
        $resetMethodName = AssociationNameGenerator::generateResetTargetsMethodName($associationKind);
        $getAssociationsName = AssociationNameGenerator::generateGetTargetEntitiesMethodName($associationKind);

        $supportMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getRealClass($targetClass);',
        ];
        $getMethodBody = [];
        $setMethodBody = [
            'if (null === $target) { $this->' . $resetMethodName . '(); return $this; }',
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);',
            '// This entity can be associated with only one another entity',
        ];
        $resetMethodBody = [];
        $getAssociationsMethodBody = [
            '$associationEntities = [];',
        ];

        foreach ($schema['relationData'] as $relationData) {
            if (!$this->isSupportedRelation($relationData)) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $relationData['field_id'];
            $fieldName = $fieldConfigId->getFieldName();
            $targetClassName = $relationData['target_entity'];

            $supportMethodBody[] = sprintf(
                'if ($className === \'%s\') { return true; }',
                $targetClassName
            );
            $getMethodBody[] = sprintf(
                'if (null !== $this->%s) { return $this->%s; }',
                $fieldName,
                $fieldName
            );
            $setMethodBody[] = sprintf(
                'if ($className === \'%s\') { $this->%s(); $this->%s = $target; return $this; }',
                $targetClassName,
                $resetMethodName,
                $fieldName
            );
            $resetMethodBody[] = sprintf(
                '$this->%s = null;',
                $fieldName
            );
            $getAssociationsMethodBody[] = str_replace(
                ['{field}'],
                [$fieldName],
                "\$entity = \$this->{field};\n"
                . "if (\$entity) {\n"
                . "    \$associationEntities[] = \$entity;\n"
                . "}"
            );
        }

        $supportMethodBody[] = 'return false;';
        $getMethodBody[] = 'return null;';
        $setMethodBody[] = 'throw new \RuntimeException(sprintf('
            . '\'The association with "%s" entity was not configured.\', $className));';
        $getAssociationsMethodBody[] = "return \$associationEntities;";

        $supportMethodDocblock = "/**\n"
            . " * Checks if this entity can be associated with the given target entity type\n"
            . " *\n"
            . " * @param string \$targetClass The class name of the target entity\n"
            . " * @return bool\n"
            . " */";
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
        $getAssociationsMethodDocblock = "/**\n"
            . " * Returns array with all associated entities\n"
            . " *\n"
            . " * @return array\n"
            . sprintf(
                ' * @deprecated since 2.0. Method "get%sTarget" should be used instead' . "\n",
                AssociationNameGenerator::classify($associationKind)
            )
            . " */";

        $class
            ->setMethod(
                $this
                    ->generateClassMethod($resetMethodName, implode("\n", $resetMethodBody))
                    ->setVisibility('private')
            )
            ->setMethod(
                $this
                    ->generateClassMethod($supportMethodName, implode("\n", $supportMethodBody), ['targetClass'])
                    ->setDocblock($supportMethodDocblock)
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
            )
            ->setMethod(
                $this
                    ->generateClassMethod($getAssociationsName, implode("\n", $getAssociationsMethodBody))
                    ->setDocblock($getAssociationsMethodDocblock)
            );
    }

    /**
     * @param array    $schema
     * @param PhpClass $class
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function generateManyToManyAssociationMethods(array $schema, PhpClass $class)
    {
        $associationKind = $this->getAssociationKind();

        $supportMethodName = AssociationNameGenerator::generateSupportTargetMethodName($associationKind);
        $getMethodName = AssociationNameGenerator::generateGetTargetsMethodName($associationKind);
        $hasMethodName = AssociationNameGenerator::generateHasTargetMethodName($associationKind);
        $addMethodName = AssociationNameGenerator::generateAddTargetMethodName($associationKind);
        $removeMethodName = AssociationNameGenerator::generateRemoveTargetMethodName($associationKind);
        $getAssociationsName = AssociationNameGenerator::generateGetTargetEntitiesMethodName($associationKind);

        $supportMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getRealClass($targetClass);',
        ];
        $getMethodBodyWithTargetClass = [];
        $getMethodBodyWithoutTargetClass = [];
        $hasMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);'
        ];
        $addMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);'
        ];
        $removeMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);'
        ];
        $getAssociationsMethodBody = [
            '$associationEntities = [];',
        ];

        foreach ($schema['relationData'] as $relationData) {
            if (!$this->isSupportedRelation($relationData)) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $relationData['field_id'];
            $fieldName = $fieldConfigId->getFieldName();
            $targetClassName = $relationData['target_entity'];

            $supportMethodBody[] = sprintf(
                'if ($className === \'%s\') { return true; }',
                $targetClassName
            );
            $getMethodBodyWithTargetClass[] = sprintf(
                '    if ($className === \'%s\') { return $this->%s; }',
                $targetClassName,
                $fieldName
            );
            $getMethodBodyWithoutTargetClass[] = str_replace(
                ['{field}'],
                [$fieldName],
                "    \$entities = \$this->{field}->toArray();\n"
                . "    if (!empty(\$entities)) { \$targets = array_merge(\$targets, \$entities); }"
            );
            $hasMethodBody[] = str_replace(
                ['{class}', '{field}'],
                [$targetClassName, $fieldName],
                "if (\$className === '{class}') { return \$this->{field}->contains(\$target); }"
            );
            $addMethodBody[] = str_replace(
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
            $getAssociationsMethodBody[] = str_replace(
                ['{field}'],
                [$fieldName],
                "\$entities = \$this->{field}->toArray();\n"
                . "if (!empty(\$entities)) {\n"
                . "    \$associationEntities = array_merge(\$associationEntities, \$entities);\n"
                . "}"
            );
        }

        $throwStmt = 'throw new \RuntimeException('
            . 'sprintf(\'The association with "%s" entity was not configured.\', $className));';

        $supportMethodBody[] = 'return false;';
        $getMethodBody = [
            'if (null === $targetClass) {',
            '    $targets = [];',
            implode("\n", $getMethodBodyWithoutTargetClass),
            '    return $targets;',
            '} else {',
            '    $className = \Doctrine\Common\Util\ClassUtils::getRealClass($targetClass);',
            implode("\n", $getMethodBodyWithTargetClass),
            '    ' . $throwStmt,
            '}',
        ];
        $hasMethodBody[] = 'return false;';
        $addMethodBody[] = $throwStmt;
        $removeMethodBody[] = $throwStmt;
        $getAssociationsMethodBody[] = "return \$associationEntities;";

        $supportMethodDocblock = "/**\n"
            . " * Checks if an entity of the given type can be associated with this entity\n"
            . " *\n"
            . " * @param string \$targetClass The class name of the target entity\n"
            . " * @return bool\n"
            . " */";
        $getMethodDocblock = "/**\n"
            . " * Gets entities associated with this entity\n"
            . " *\n"
            . " * @param string|null \$targetClass The class name of the target entity\n"
            . " * @return object[]\n"
            . " */";
        $hasMethodDocblock = "/**\n"
            . " * Checks is the given entity is associated with this entity\n"
            . " *\n"
            . " * @param object \$target Any configurable entity that can be associated with this type of entity\n"
            . " * @return bool\n"
            . " */";
        $addMethodDocblock = "/**\n"
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
        $getAssociationsMethodDocblock = "/**\n"
            . " * Returns array with all associated entities\n"
            . " *\n"
            . " * @return array\n"
            . sprintf(
                ' * @deprecated since 2.0. Method "get%1$sTargets" without parameters should be used'
                . ' instead of "get%1$sTargetEntities"' . "\n",
                AssociationNameGenerator::classify($associationKind)
            )
            . " */";
        $class
            ->setMethod(
                $this
                    ->generateClassMethod($supportMethodName, implode("\n", $supportMethodBody), ['targetClass'])
                    ->setDocblock($supportMethodDocblock)
            )
            ->setMethod(
                $this
                    ->generateClassMethod($getMethodName, implode("\n", $getMethodBody), [['targetClass', null]])
                    ->setDocblock($getMethodDocblock)
            )
            ->setMethod(
                $this
                    ->generateClassMethod($hasMethodName, implode("\n", $hasMethodBody), ['target'])
                    ->setDocblock($hasMethodDocblock)
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
            )
            ->setMethod(
                $this
                    ->generateClassMethod($getAssociationsName, implode("\n", $getAssociationsMethodBody))
                    ->setDocblock($getAssociationsMethodDocblock)
            );
    }
}
