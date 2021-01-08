<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\PhpUtils\ClassGenerator;

/**
 * This class provides PHP code generation logic for entities with associations.
 */
abstract class AbstractAssociationEntityGeneratorExtension extends AbstractEntityGeneratorExtension
{
    public function supports(array $schema): bool
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

    public function generate(array $schema, ClassGenerator $class): void
    {
        $this->generateAssociationMethods($schema, $class);
    }

    /**
     * Returns the association kind (e.g. 'activity', etc.) or NULL for unclassified (default) association.
     */
    protected function getAssociationKind(): ?string
    {
        return null;
    }

    /**
     * Gets the type of the association. For example manyToOne or manyToMany
     * {@see \Oro\Bundle\EntityExtendBundle\Extend\RelationType}
     */
    protected function getAssociationType(): string
    {
        return RelationType::MANY_TO_ONE;
    }

    protected function isSupportedRelation(array $relationData): bool
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
     * @throws \RuntimeException If PHP code cannot be generated
     */
    protected function generateAssociationMethods(array $schema, ClassGenerator $class): void
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function generateMultipleManyToOneAssociationMethods(array $schema, ClassGenerator $class): void
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

        $supportMethodDocblock = "Checks if this entity can be associated with the given target entity type\n\n"
            . "@param string \$targetClass The class name of the target entity\n"
            . "@return bool\n";
        $getMethodDocblock = "Gets the entities this entity is associated with\n\n"
            . "@return object[]\n";
        $hasMethodDocblock = "Checks is the given entity is associated with this entity\n\n"
            . "@param object \$target Any configurable entity that can be associated with this type of entity\n"
            . "@return bool\n";
        $addMethodDocblock = "Associates the given entity with this entity\n\n"
            . "@param object \$target Any configurable entity that can be associated with this type of entity\n"
            . "@return object This object\n";
        $removeMethodDocblock = "Removes the association of the given entity and this entity\n\n"
            . "@param object \$target Any configurable entity that can be associated with this type of entity\n"
            . "@return object This object\n";

        $class->addMethod($supportMethodName)
            ->addBody(\implode("\n", $supportMethodBody))
            ->addComment($supportMethodDocblock)
            ->addParameter('targetClass');

        $class->addMethod($getMethodName)
            ->addBody(\implode("\n", $getMethodBody))
            ->addComment($getMethodDocblock);

        $class->addMethod($hasMethodName)
            ->addBody(\implode("\n", $hasMethodBody))
            ->addComment($hasMethodDocblock)
            ->addParameter('target');

        $class->addMethod($addMethodName)
            ->addBody(\implode("\n", $addMethodBody))
            ->addComment($addMethodDocblock)
            ->addParameter('target');

        $class->addMethod($removeMethodName)
            ->addBody(\implode("\n", $removeMethodBody))
            ->addComment($removeMethodDocblock)
            ->addParameter('target');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function generateManyToOneAssociationMethods(array $schema, ClassGenerator $class): void
    {
        $associationKind = $this->getAssociationKind();

        $supportMethodName = AssociationNameGenerator::generateSupportTargetMethodName($associationKind);
        $getMethodName = AssociationNameGenerator::generateGetTargetMethodName($associationKind);
        $setMethodName = AssociationNameGenerator::generateSetTargetMethodName($associationKind);
        $resetMethodName = AssociationNameGenerator::generateResetTargetsMethodName($associationKind);

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
        }

        $supportMethodBody[] = 'return false;';
        $getMethodBody[] = 'return null;';
        $setMethodBody[] = 'throw new \RuntimeException(sprintf('
            . '\'The association with "%s" entity was not configured.\', $className));';

        $supportMethodDocblock = "Checks if this entity can be associated with the given target entity type\n\n"
            . "@param string \$targetClass The class name of the target entity\n"
            . "@return bool\n";
        $getMethodDocblock = "Gets the entity this entity is associated with\n\n"
            . "@return object|null Any configurable entity\n";
        $setMethodDocblock = "Sets the entity this entity is associated with\n\n"
            . "@param object \$target Any configurable entity that can be associated with this type of entity\n"
            . "@return object This object\n";

        $class->addMethod($resetMethodName)
            ->setPrivate()
            ->addBody(\implode("\n", $resetMethodBody));

        $class->addMethod($supportMethodName)
            ->addBody(\implode("\n", $supportMethodBody))
            ->addComment($supportMethodDocblock)
            ->addParameter('targetClass');

        $class->addMethod($getMethodName)
            ->addBody(\implode("\n", $getMethodBody))
            ->addComment($getMethodDocblock);

        $class->addMethod($setMethodName)
            ->addBody(\implode("\n", $setMethodBody))
            ->addComment($setMethodDocblock)
            ->addParameter('target');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function generateManyToManyAssociationMethods(array $schema, ClassGenerator $class): void
    {
        $associationKind = $this->getAssociationKind();

        $supportMethodName = AssociationNameGenerator::generateSupportTargetMethodName($associationKind);
        $getMethodName = AssociationNameGenerator::generateGetTargetsMethodName($associationKind);
        $hasMethodName = AssociationNameGenerator::generateHasTargetMethodName($associationKind);
        $addMethodName = AssociationNameGenerator::generateAddTargetMethodName($associationKind);
        $removeMethodName = AssociationNameGenerator::generateRemoveTargetMethodName($associationKind);

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

        $supportMethodDocblock = "Checks if an entity of the given type can be associated with this entity\n\n"
            . "@param string \$targetClass The class name of the target entity\n"
            . "@return bool\n";
        $getMethodDocblock = "Gets entities associated with this entity\n\n"
            . "@param string|null \$targetClass The class name of the target entity\n"
            . "@return object[]\n";
        $hasMethodDocblock = "Checks is the given entity is associated with this entity\n\n"
            . "@param object \$target Any configurable entity that can be associated with this type of entity\n"
            . "@return bool\n";
        $addMethodDocblock = "Associates the given entity with this entity\n\n"
            . "@param object \$target Any configurable entity that can be associated with this type of entity\n"
            . "@return object This object\n";
        $removeMethodDocblock = "Removes the association of the given entity and this entity\n\n"
            . "@param object \$target Any configurable entity that can be associated with this type of entity\n"
            . "@return object This object\n";

        $class->addMethod($supportMethodName)
            ->addBody(\implode("\n", $supportMethodBody))
            ->setComment($supportMethodDocblock)
            ->addParameter('targetClass');

        $class->addMethod($getMethodName)
            ->addBody(\implode("\n", $getMethodBody))
            ->addComment($getMethodDocblock)
            ->addParameter('targetClass')->setDefaultValue(null);

        $class->addMethod($hasMethodName)
            ->addBody(\implode("\n", $hasMethodBody))
            ->addComment($hasMethodDocblock)
            ->addParameter('target');

        $class->addMethod($addMethodName)
            ->addBody(\implode("\n", $addMethodBody))
            ->addComment($addMethodDocblock)
            ->addParameter('target');

        $class->addMethod($removeMethodName)
            ->addBody(\implode("\n", $removeMethodBody))
            ->addComment($removeMethodDocblock)
            ->addParameter('target');
    }
}
