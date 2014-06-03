<?php

namespace Oro\Bundle\NoteBundle\Tools;

use CG\Generator\PhpClass;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ClassBuilder;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGeneratorExtension;

class NoteGeneratorExtension implements ExtendEntityGeneratorExtension
{
    /**
     * Check if generator extension supports configuration pre-processing or can generate code
     *
     * @param string $actionType pre-process or generate
     * @param array  $schemas    whole schemas when actionType is pre-process,
     *                           entity schema when actionType is generate
     *
     * @return bool
     */
    public function supports($actionType, array $schemas)
    {
        return ExtendEntityGeneratorExtension::ACTION_GENERATE == $actionType &&
            $schemas['class'] == 'Oro\Bundle\NoteBundle\Entity\Note' &&
            !empty($schemas['relation']);
    }

    /**
     * Apply extension to entity configuration before it will be generated as PHP, YAML files
     *
     * @param array $schemas
     *
     * @return void
     */
    public function preProcessEntityConfiguration(array &$schemas)
    {
        // nothing to pre-process here
    }

    /**
     * @param array    $schema
     * @param PhpClass $class
     *
     * @return void
     */
    public function generate(array $schema, PhpClass $class)
    {
        $classBuilder   = new ClassBuilder();


        $relationData = empty($schema['relationData']) ? [] : $schema['relationData'];


        $relationNames     = [];
        $supportedEntities = [];
        $resetMethodsBody  = [];

        foreach ($relationData as $relationItem) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $relationItem['field_id'];

            $fieldName                 = $fieldConfigId->getFieldName();
            $targetClassName           = $relationItem['target_entity'];
            $relationNames[$fieldName] = $targetClassName;

            $resetMethodsBody[] = sprintf(
                '$this->%s = null;',
                $fieldName
            );
            $supportedEntities[] = sprintf("'%s'", $targetClassName);
        }

        $methodBody = [
            '// Note can have only one related entity',
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);',
            'if (!in_array($className, [' . implode(', ', $supportedEntities) . ']) {',
            '    throw new \Exception(sprintf("There\'s no relation for %s entity class.", $className));',
            '}'
        ];


        foreach ($relationNames as $fieldName => $targetClassName) {
            $methodBody[] = sprintf(
                'if ($className == \'%s\') { $this->__reset(); $this->%s = %s; }',
                $targetClassName,
                $fieldName,
                '$target'
            );
        }

        $class
            ->setMethod(
                $classBuilder->generateClassMethod(
                    '__reset',
                    implode("\n", $resetMethodsBody)
                )->setVisibility('private')
            )
            ->setMethod(
                $classBuilder->generateClassMethod(
                    'setTarget',
                    implode("\n", $methodBody),
                    ['target']
                )
                ->setDocblock("/**\n * @param object \$target any object that can have notes\n */")
            );
    }

}
