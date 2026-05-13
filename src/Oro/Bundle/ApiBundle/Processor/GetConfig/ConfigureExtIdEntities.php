<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\FieldDescriptionUtil;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Configures API resources that use an identifier from an external system.
 */
class ConfigureExtIdEntities implements ProcessorInterface
{
    public function __construct(
        private readonly array $extIdEntities,
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $entityClass = $context->getClassName();
        $definition = $context->getResult();
        $extIdFieldName = $this->getExtIdFieldName($entityClass, $definition);
        if (null === $extIdFieldName) {
            return;
        }
        $dbIdFieldName = $this->getDbIdFieldName($entityClass);
        if (null === $dbIdFieldName) {
            return;
        }

        $idFieldName = $definition->findFieldNameByPropertyPath($extIdFieldName);
        if (null === $idFieldName) {
            $idFieldName = $extIdFieldName;
            $definition->addField($idFieldName);
        }
        $definition->setIdentifierFieldNames([$idFieldName]);
        if (!$context->hasExtra(FilterIdentifierFieldsConfigExtra::NAME)) {
            $this->addDbIdFieldDefinition($definition, $entityClass, $dbIdFieldName, $context);
        }
    }

    private function addDbIdFieldDefinition(
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $dbIdFieldName,
        ConfigContext $context
    ): void {
        $dbIdField = $definition->getOrAddField('dbId');
        if (!$dbIdField->hasPropertyPath()) {
            $dbIdField->setPropertyPath($dbIdFieldName);
        }
        $isReadOnlyField =
            $dbIdField->getPropertyPath() === $dbIdFieldName
            && $this->doctrineHelper->getEntityMetadataForClass($entityClass)->usesIdGenerator();
        if ($isReadOnlyField && null === $dbIdField->getFormOption('mapped')) {
            $dbIdField->setFormOption('mapped', false);
        }
        if ($context->hasExtra(DescriptionsConfigExtra::NAME) && !$dbIdField->hasDescription()) {
            $dbIdFieldDescription = 'A unique identifier in the database.';
            if ($isReadOnlyField && $this->isChangeResourceAction($context->getTargetAction())) {
                $dbIdFieldDescription = FieldDescriptionUtil::addReadOnlyFieldNote($dbIdFieldDescription);
            }
            $dbIdField->setDescription($dbIdFieldDescription);
        }
    }

    private function getExtIdFieldName(string $entityClass, EntityDefinitionConfig $definition): ?string
    {
        if ($definition->getIdentifierFieldNames()) {
            // some custom identifier is already configured for the API resource
            return null;
        }

        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return null;
        }

        // check whether the API resource uses an identifier from an external system
        $extIdFieldName = $this->extIdEntities[$entityClass] ?? null;
        if (null === $extIdFieldName && $this->isInheritanceMappingEntity($entityClass)) {
            foreach ($this->extIdEntities as $class => $field) {
                if (is_a($entityClass, $class, true)) {
                    $extIdFieldName = $field;
                    break;
                }
            }
        }

        return $extIdFieldName;
    }

    private function isInheritanceMappingEntity(string $entityClass): bool
    {
        return !$this->doctrineHelper->getEntityMetadataForClass($entityClass)->isInheritanceTypeNone();
    }

    private function getDbIdFieldName(string $entityClass): ?string
    {
        $dbIdFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        if (\count($dbIdFieldNames) !== 1) {
            // entities with a composite identifier are not supported
            return null;
        }

        return $dbIdFieldNames[0];
    }

    private function isChangeResourceAction(?string $action): bool
    {
        return ApiAction::CREATE === $action || ApiAction::UPDATE === $action;
    }
}
