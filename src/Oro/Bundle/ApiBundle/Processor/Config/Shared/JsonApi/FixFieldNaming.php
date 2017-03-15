<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Tries to rename fields if they are equal to reserved words or not conform JSON.API specification.
 * * "type" field is renamed to {short class name} + "Type"
 * * "id" field is renamed to {short class name} + "Id" in case if it is not the identifier of an entity
 * * the single identifier field is renamed to "id" if it has a different name
 */
class FixFieldNaming implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->hasFields()) {
            // nothing to fix
            return;
        }

        $entityClass = $context->getClassName();
        // process "type" field
        if ($definition->hasField(JsonApiDoc::TYPE)) {
            $this->renameReservedField($definition, $entityClass, JsonApiDoc::TYPE);
        }
        // process "id" field
        if ($definition->hasField(JsonApiDoc::ID)
            && !$this->isIdentifierField($definition, JsonApiDoc::ID)
        ) {
            $this->renameReservedField($definition, $entityClass, JsonApiDoc::ID);
        }
        // make sure the identifier field has "id" name
        $idFieldNames = $definition->getIdentifierFieldNames();
        if (count($idFieldNames) === 1) {
            $idFieldName = reset($idFieldNames);
            if (JsonApiDoc::ID !== $idFieldName) {
                $this->renameIdField($definition, $idFieldName, JsonApiDoc::ID);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string|null            $entityClass
     * @param string                 $fieldName
     *
     * @throws \RuntimeException if a field cannot be renamed
     */
    protected function renameReservedField(EntityDefinitionConfig $definition, $entityClass, $fieldName)
    {
        $newFieldName = lcfirst($this->getShortClassName($entityClass)) . ucfirst($fieldName);
        if ($definition->hasField($newFieldName)) {
            throw new RuntimeException(
                sprintf(
                    'The "%s" reserved word cannot be used as a field name'
                    . ' and it cannot be renamed to "%s" because a field with this name already exists.',
                    $fieldName,
                    $newFieldName
                )
            );
        }

        // do renaming
        $field = $definition->getField($fieldName);
        if (!$field->hasPropertyPath()) {
            $field->setPropertyPath($fieldName);
        }
        $definition->removeField($fieldName);
        $definition->addField($newFieldName, $field);
        // rename identifier field if needed
        $idFieldNames = $definition->getIdentifierFieldNames();
        $idFieldNameIndex = array_search($fieldName, $idFieldNames, true);
        if (false !== $idFieldNameIndex) {
            $idFieldNames[$idFieldNameIndex] = $newFieldName;
            $definition->setIdentifierFieldNames($idFieldNames);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $fieldName
     * @param string                 $newFieldName
     */
    protected function renameIdField(EntityDefinitionConfig $definition, $fieldName, $newFieldName)
    {
        $field = $definition->getField($fieldName);
        if (!$field->hasPropertyPath()) {
            $definition->removeField($fieldName);
            $field->setPropertyPath($fieldName);
            $definition->addField($newFieldName, $field);
            $definition->setIdentifierFieldNames([$newFieldName]);
        }
    }

    /**
     * Checks whether the given field is an identifier of the given entity
     *
     * @param EntityDefinitionConfig $definition
     * @param string                 $fieldName
     *
     * @return bool
     */
    protected function isIdentifierField(EntityDefinitionConfig $definition, $fieldName)
    {
        $idFieldNames = $definition->getIdentifierFieldNames();

        return count($idFieldNames) === 1 && reset($idFieldNames) === $fieldName;
    }

    /**
     * Gets the short name of the class, the part without the namespace
     *
     * @param string $className The full name of a class
     *
     * @return string
     */
    protected function getShortClassName($className)
    {
        $lastDelimiter = strrpos($className, '\\');

        return false === $lastDelimiter
            ? $className
            : substr($className, $lastDelimiter + 1);
    }
}
