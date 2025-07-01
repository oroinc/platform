<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\EntityDescriptionHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\FieldsDescriptionHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\FiltersDescriptionHelper;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds human-readable descriptions for:
 * * entity
 * * fields
 * * filters
 * * identifier field
 * * "createdAt" and "updatedAt" fields
 * * ownership fields such as "owner" and "organization"
 * * fields for entities represent enumerations
 * By performance reasons all these actions are done in one processor.
 */
class CompleteDescriptions implements ProcessorInterface
{
    private ResourcesProvider $resourcesProvider;
    private EntityDescriptionHelper $entityDescriptionHelper;
    private FieldsDescriptionHelper $fieldsDescriptionHelper;
    private FiltersDescriptionHelper $filtersDescriptionHelper;

    public function __construct(
        ResourcesProvider $resourcesProvider,
        EntityDescriptionHelper $entityDescriptionHelper,
        FieldsDescriptionHelper $fieldsDescriptionHelper,
        FiltersDescriptionHelper $filtersDescriptionHelper
    ) {
        $this->resourcesProvider = $resourcesProvider;
        $this->entityDescriptionHelper = $entityDescriptionHelper;
        $this->fieldsDescriptionHelper = $fieldsDescriptionHelper;
        $this->filtersDescriptionHelper = $filtersDescriptionHelper;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $targetAction = $context->getTargetAction();
        if (!$targetAction) {
            // descriptions cannot be set for undefined target action
            return;
        }

        $requestType = $context->getRequestType();
        $entityClass = $context->getClassName();
        $definition = $context->getResult();
        $isInherit = false;
        if (ExtendHelper::getParentClassName($entityClass)
            && $this->resourcesProvider->isResourceKnown($entityClass, $context->getVersion(), $requestType)
        ) {
            $isInherit = true;
        }

        $this->entityDescriptionHelper->setDescriptionForEntity(
            $definition,
            $requestType,
            $entityClass,
            $isInherit,
            $targetAction,
            $context->isCollection(),
            $context->getAssociationName(),
            $context->getParentClassName()
        );
        if (ApiAction::OPTIONS !== $targetAction) {
            $this->fieldsDescriptionHelper->setDescriptionsForFields(
                $definition,
                $requestType,
                $entityClass,
                $isInherit,
                $this->getTargetActionForFields($context)
            );
            $filters = $context->getFilters();
            if (null !== $filters) {
                $this->filtersDescriptionHelper->setDescriptionsForFilters(
                    $filters,
                    $definition,
                    $requestType,
                    $entityClass,
                    $isInherit
                );
            }
        }
    }

    private function getTargetActionForFields(ConfigContext $context): ?string
    {
        /** @var DescriptionsConfigExtra|null $descriptionsConfigExtra */
        $descriptionsConfigExtra = $context->getExtra(DescriptionsConfigExtra::NAME);
        if (null !== $descriptionsConfigExtra) {
            return $descriptionsConfigExtra->getDocumentationAction() ?? $context->getTargetAction();
        }

        return $context->getTargetAction();
    }
}
