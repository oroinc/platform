<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntityConfigInterface;

/**
 * Tries to rename fields if they are equal to reserved words or not conform JSON:API specification.
 * * "type" field is renamed to {short class name} + "Type"
 * * "id" field is renamed to {short class name} + "Id" if it is not the identifier of an entity
 * * the single identifier field is renamed to "id" if it has a different name
 */
class FixFieldNaming implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->hasFields()) {
            // nothing to fix
            return;
        }

        $renamedFields = [];
        $entityClass = $context->getClassName();
        if ($definition->hasField(JsonApiDoc::TYPE)) {
            $this->renameReservedField($renamedFields, $definition, $entityClass, JsonApiDoc::TYPE);
        }
        $this->processIdField($renamedFields, $definition, $entityClass);
        if ($renamedFields) {
            if ($context->hasExtra(FiltersConfigExtra::NAME)) {
                $this->renameFields($renamedFields, $context->getFilters());
            }
            if ($context->hasExtra(SortersConfigExtra::NAME)) {
                $this->renameFields($renamedFields, $context->getSorters());
            }
        }
    }

    private function renameReservedField(
        array &$renamedFields,
        EntityDefinitionConfig $definition,
        ?string $entityClass,
        string $fieldName
    ): void {
        $newFieldName = lcfirst($this->getShortClassName($entityClass)) . ucfirst($fieldName);
        if ($definition->hasField($newFieldName)) {
            throw new \RuntimeException(\sprintf(
                'The "%s" reserved word cannot be used as a field name'
                . ' and it cannot be renamed to "%s" because a field with this name already exists.',
                $fieldName,
                $newFieldName
            ));
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
        $renamedFields[$fieldName] = $newFieldName;
    }

    private function processIdField(
        array &$renamedFields,
        EntityDefinitionConfig $definition,
        string $entityClass
    ): void {
        $idFieldNames = $definition->getIdentifierFieldNames();
        $numberOfIdFields = \count($idFieldNames);
        if ($numberOfIdFields === 1) {
            $idFieldName = reset($idFieldNames);
            if (JsonApiDoc::ID !== $idFieldName) {
                if ($definition->hasField(JsonApiDoc::ID)) {
                    $this->renameReservedField($renamedFields, $definition, $entityClass, JsonApiDoc::ID);
                }
                $this->renameIdField($renamedFields, $definition, $idFieldName);
            }
        } elseif ($numberOfIdFields > 1) {
            if ($definition->hasField(JsonApiDoc::ID)) {
                $this->renameReservedField($renamedFields, $definition, $entityClass, JsonApiDoc::ID);
            }
        }
    }

    private function renameIdField(array &$renamedFields, EntityDefinitionConfig $definition, string $fieldName): void
    {
        $field = $definition->getField($fieldName);
        if (null !== $field) {
            $fieldPropertyPath = $field->getPropertyPath($fieldName);
            $definition->removeField($fieldName);
            $field->setPropertyPath($fieldPropertyPath);
            $definition->addField(JsonApiDoc::ID, $field);
            $definition->setIdentifierFieldNames([JsonApiDoc::ID]);
            $renamedFields[$fieldName] = JsonApiDoc::ID;
        }
    }

    private function renameFields(array $renamedFields, ?EntityConfigInterface $fields): void
    {
        if (null === $fields || !$fields->hasFields()) {
            return;
        }

        foreach ($renamedFields as $oldFieldName => $newFieldName) {
            if ($fields->hasField($oldFieldName)) {
                $field = $fields->getField($oldFieldName);
                $fields->removeField($oldFieldName);
                $fields->addField($newFieldName, $field);
            }
        }
    }

    /**
     * Gets the short name of the class, the part without the namespace
     */
    private function getShortClassName(string $className): string
    {
        $lastDelimiter = strrpos($className, '\\');

        return false === $lastDelimiter
            ? $className
            : substr($className, $lastDelimiter + 1);
    }
}
