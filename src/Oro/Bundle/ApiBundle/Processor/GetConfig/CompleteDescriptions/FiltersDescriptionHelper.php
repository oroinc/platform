<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\InheritDocUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The helper that is used to set descriptions of filters.
 */
class FiltersDescriptionHelper
{
    private const FIELD_FILTER_DESCRIPTION = 'Filter records by \'%s\' field.';
    private const ASSOCIATION_FILTER_DESCRIPTION = 'Filter records by \'%s\' relationship.';

    private TranslatorInterface $translator;
    private ResourceDocParserProvider $resourceDocParserProvider;
    private DescriptionProcessor $descriptionProcessor;

    public function __construct(
        TranslatorInterface $translator,
        ResourceDocParserProvider $resourceDocParserProvider,
        DescriptionProcessor $descriptionProcessor
    ) {
        $this->translator = $translator;
        $this->resourceDocParserProvider = $resourceDocParserProvider;
        $this->descriptionProcessor = $descriptionProcessor;
    }

    public function setDescriptionsForFilters(
        FiltersConfig $filters,
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $entityClass,
        bool $isInherit
    ): void {
        $resourceDocParser = $this->resourceDocParserProvider->getResourceDocParser($requestType);
        $fields = $filters->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($isInherit || !$field->hasDescription()) {
                $description = $resourceDocParser->getFilterDocumentation($entityClass, $fieldName);
                if ($description) {
                    if (InheritDocUtil::hasInheritDoc($description)) {
                        $description = InheritDocUtil::replaceInheritDoc(
                            $description,
                            $this->getFilterDefaultDescription($fieldName, $definition->getField($fieldName))
                        );
                    }
                    $field->setDescription($description);
                } elseif (!$field->getDescription()) {
                    $field->setDescription(
                        $this->getFilterDefaultDescription($fieldName, $definition->getField($fieldName))
                    );
                }
            } else {
                $description = $field->getDescription();
                if ($description instanceof Label) {
                    $field->setDescription($this->trans($description));
                }
            }

            $description = $field->getDescription();
            if ($description) {
                $field->setDescription($this->descriptionProcessor->process($description, $requestType));
            }
        }
    }

    private function getFilterDefaultDescription(string $fieldName, ?EntityDefinitionFieldConfig $fieldConfig): string
    {
        if (null !== $fieldConfig && $fieldConfig->hasTargetEntity()) {
            return sprintf(self::ASSOCIATION_FILTER_DESCRIPTION, $fieldName);
        }

        return sprintf(self::FIELD_FILTER_DESCRIPTION, $fieldName);
    }

    private function trans(Label $label): ?string
    {
        return $label->trans($this->translator) ?: null;
    }
}
