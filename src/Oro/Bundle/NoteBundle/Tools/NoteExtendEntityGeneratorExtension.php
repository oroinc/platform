<?php

namespace Oro\Bundle\NoteBundle\Tools;

use CG\Generator\PhpClass;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ClassBuilder;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGeneratorExtension;
use Oro\Bundle\NoteBundle\Entity\Note;

class NoteExtendEntityGeneratorExtension extends ExtendEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports($actionType, array $schemas)
    {
        return
            ExtendEntityGenerator::ACTION_GENERATE === $actionType
            && $schemas['class'] === Note::ENTITY_NAME
            && !empty($schemas['relation']);
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
        $resetMethodsBody  = [];

        foreach ($relationData as $relationItem) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $relationItem['field_id'];

            $fieldName                 = $fieldConfigId->getFieldName();
            $targetClassName           = $relationItem['target_entity'];
            $relationNames[$fieldName] = $targetClassName;

            $resetMethodsBody[]  = sprintf(
                '$this->%s = null;',
                $fieldName
            );
            $supportedEntities[] = sprintf("'%s'", $targetClassName);
        }

        $getMethodBody = [];
        $setMethodBody = [
            '$className = \Doctrine\Common\Util\ClassUtils::getClass($target);',
            '// The note can be associated with one entity only',
        ];

        foreach ($relationNames as $fieldName => $targetClassName) {
            $getMethodBody[] = sprintf(
                'if (null !== $this->%s) { return $this->%s; }',
                $fieldName,
                $fieldName
            );
            $setMethodBody[] = sprintf(
                'if ($className === \'%s\') { $this->resetTargets(); $this->%s = %s; return $this; }',
                $targetClassName,
                $fieldName,
                '$target'
            );
        }

        $getMethodBody[] = 'return null;';
        $setMethodBody[] = 'throw new \RuntimeException(sprintf('
            . '\'The association with "%s" entity was not configured.\', $className));';

        $getMethodDocblock = "/**\n"
            . " * Gets the entity this note is associated with\n"
            . " *\n"
            . " * @return object|null Any configurable entity\n"
            . " */";
        $setMethodDocblock = "/**\n"
            . " * Sets the entity this note is associated with\n"
            . " *\n"
            . " * @param object \$target Any configurable entity that can have notes\n"
            . " * @return object This object\n"
            . " */";

        $class
            ->setMethod(
                $classBuilder
                    ->generateClassMethod('resetTargets', implode("\n", $resetMethodsBody))
                    ->setVisibility('private')
            )
            ->setMethod(
                $classBuilder
                    ->generateClassMethod('getTarget', implode("\n", $getMethodBody))
                    ->setDocblock($getMethodDocblock)
            )
            ->setMethod(
                $classBuilder
                    ->generateClassMethod('setTarget', implode("\n", $setMethodBody), ['target'])
                    ->setDocblock($setMethodDocblock)
            );
    }
}
