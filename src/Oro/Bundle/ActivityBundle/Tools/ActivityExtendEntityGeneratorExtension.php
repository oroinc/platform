<?php

namespace Oro\Bundle\ActivityBundle\Tools;

use CG\Generator\PhpClass;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ClassBuilder;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGeneratorExtension;

class ActivityExtendEntityGeneratorExtension extends ExtendEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports($actionType, array $schemas)
    {
        return ExtendEntityGenerator::ACTION_GENERATE == $actionType && !empty($schemas['relation']);
    }

    /**
     * @param array    $schema
     * @param PhpClass $class
     *
     * @return void
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

        $getMethodBody = $addMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);',
            'if (!in_array($className, [' . implode(', ', $supportedEntities) . '])) {',
            '    throw new \RuntimeException(',
                    'sprintf(\'The association with "%s" entity was not configured.\', $className)',
                 ');',
            '}',
        ];

        foreach ($relationNames as $fieldName => $targetClassName) {
            $getMethodBody[] = sprintf(
                'if ($className == \'%s\') { return $this->%s; }',
                $targetClassName,
                $fieldName
            );

            $addMethodBody[] = sprintf(
                'if ($className == \'%s\') { $this->%s[] = %s; }',
                $targetClassName,
                $fieldName,
                '$target'
            );
        }

        $getMethodDocblock = "/**\n"
            . " * Gets the entity this note is associated with\n"
            . " *\n"
            . " * @return object|null Any configurable entity\n"
            . " */";
        $addMethodDocblock = "/**\n"
            . " * Add associated entity\n"
            . " *\n"
            . " * @param object \$target Any configurable entity that can be used with associations\n"
            . " */";

        $class
            ->setMethod(
                $classBuilder
                    ->generateClassMethod('getTargets', implode("\n", $getMethodBody))
                    ->setDocblock($getMethodDocblock)
            )
            ->setMethod(
                $classBuilder
                    ->generateClassMethod('addTarget', implode("\n", $addMethodBody), ['target'])
                    ->setDocblock($addMethodDocblock)
            );
    }
}
