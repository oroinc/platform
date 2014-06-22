<?php

namespace Oro\Bundle\ActivityBundle\Tools;

use CG\Generator\PhpClass;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ClassBuilder;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGeneratorExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityExtendEntityGeneratorExtension extends ExtendEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports($actionType, array $schemas)
    {
        $result =
            ExtendEntityGenerator::ACTION_GENERATE === $actionType
            && !empty($schemas['relation'])
            && !empty($schemas['relationData']);

        if ($result) {
            $result = false;
            foreach ($schemas['relationData'] as $relationData) {
                /** @var FieldConfigId $fieldConfig */
                $fieldConfig = $relationData['field_id'];

                if ($fieldConfig instanceof FieldConfigId
                    && $fieldConfig->getFieldType() === 'manyToMany'
                    && $fieldConfig->getFieldName() === ExtendHelper::buildAssociationName(
                        $relationData['target_entity']
                    )
                ) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, PhpClass $class)
    {
        $classBuilder = new ClassBuilder();

        $relationData = empty($schema['relationData']) ? [] : $schema['relationData'];

        $relationNames     = [];
        $supportedEntities = [];

        foreach ($relationData as $relationItem) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $relationItem['field_id'];

            $fieldName                 = $fieldConfigId->getFieldName();
            $targetClassName           = $relationItem['target_entity'];
            $relationNames[$fieldName] = $targetClassName;
            $supportedEntities[]       = sprintf("'%s'", $targetClassName);
        }

        $getMethodBody    = [
            '$className = \Doctrine\Common\Util\ClassUtils::getRealClass($targetClass);'
        ];
        $addMethodBody    = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);'
        ];
        $removeMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);'
        ];

        foreach ($relationNames as $fieldName => $targetClassName) {
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

        $getMethodDocblock = "/**\n"
            . " * Gets entities of the given type associated with this activity entity\n"
            . " *\n"
            . " * @param string \$targetClass The class name of the target entity\n"
            . " * @return object[]\n"
            . " */";
        $addMethodDocblock = "/**\n"
            . " * Associates the given entity with this activity entity\n"
            . " *\n"
            . " * @param object \$target Any configurable entity that can be associated with this activity\n"
            . " * @return object This object\n"
            . " */";
        $removeMethodDocblock = "/**\n"
            . " * Removes the association of the given entity with this activity entity\n"
            . " *\n"
            . " * @param object \$target Any configurable entity that can be associated with this activity\n"
            . " * @return object This object\n"
            . " */";

        $class
            ->setMethod(
                $classBuilder
                    ->generateClassMethod('getActivityTargets', implode("\n", $getMethodBody), ['targetClass'])
                    ->setDocblock($getMethodDocblock)
            )
            ->setMethod(
                $classBuilder
                    ->generateClassMethod('addActivityTarget', implode("\n", $addMethodBody), ['target'])
                    ->setDocblock($addMethodDocblock)
            )
            ->setMethod(
                $classBuilder
                    ->generateClassMethod('removeActivityTarget', implode("\n", $removeMethodBody), ['target'])
                    ->setDocblock($removeMethodDocblock)
            );
    }
}
