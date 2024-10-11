<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProvider;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\InheritDocUtil;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The helper that is used to set descriptions of entities.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityDescriptionHelper implements ResetInterface
{
    private const GET_LIST_MAX_RESULTS_DESCRIPTION =
        '<p><strong>Note:</strong>'
        . ' The maximum number of records this endpoint can return is {max_results}.</p>';
    private const DELETE_LIST_MAX_RESULTS_DESCRIPTION =
        '<p><strong>Note:</strong>'
        . ' The maximum number of records this endpoint can delete at a time is {max_results}.</p>';

    private EntityDescriptionProvider $entityDescriptionProvider;
    private EntityNameProvider $entityNameProvider;
    private TranslatorInterface $translator;
    private ResourceDocProvider $resourceDocProvider;
    private ResourceDocParserProvider $resourceDocParserProvider;
    private DescriptionProcessor $descriptionProcessor;
    private IdentifierDescriptionHelper $identifierDescriptionHelper;
    private int $maxEntitiesLimit;
    private int $maxDeleteEntitiesLimit;

    /** @var array [entity class => entity description, ...] */
    private array $singularEntityDescriptions = [];
    /** @var array [entity class => entity description, ...] */
    private array $pluralEntityDescriptions = [];
    /** @var array [association name => humanized association name, ...] */
    private array $humanizedAssociationNames = [];

    public function __construct(
        EntityDescriptionProvider $entityDescriptionProvider,
        EntityNameProvider $entityNameProvider,
        TranslatorInterface $translator,
        ResourceDocProvider $resourceDocProvider,
        ResourceDocParserProvider $resourceDocParserProvider,
        DescriptionProcessor $descriptionProcessor,
        IdentifierDescriptionHelper $identifierDescriptionHelper,
        int $maxEntitiesLimit,
        int $maxDeleteEntitiesLimit
    ) {
        $this->entityDescriptionProvider = $entityDescriptionProvider;
        $this->entityNameProvider = $entityNameProvider;
        $this->translator = $translator;
        $this->resourceDocProvider = $resourceDocProvider;
        $this->resourceDocParserProvider = $resourceDocParserProvider;
        $this->descriptionProcessor = $descriptionProcessor;
        $this->identifierDescriptionHelper = $identifierDescriptionHelper;
        $this->maxEntitiesLimit = $maxEntitiesLimit;
        $this->maxDeleteEntitiesLimit = $maxDeleteEntitiesLimit;
    }

    #[\Override]
    public function reset(): void
    {
        $this->singularEntityDescriptions = [];
        $this->pluralEntityDescriptions = [];
        $this->humanizedAssociationNames = [];
    }

    public function setDescriptionForEntity(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $entityClass,
        bool $isInherit,
        string $targetAction,
        bool $isCollection,
        ?string $associationName,
        ?string $parentEntityClass
    ): void {
        $this->identifierDescriptionHelper->setDescriptionForEntityIdentifier($definition);

        if ($definition->hasDescription()) {
            $description = $definition->getDescription();
            if ($description instanceof Label) {
                $definition->setDescription($this->trans($description));
            }
        } else {
            if ($associationName) {
                $description = $this->resourceDocProvider->getSubresourceDescription(
                    $targetAction,
                    $this->getAssociationDescription($associationName),
                    $isCollection
                );
            } else {
                $description = $this->resourceDocProvider->getResourceDescription(
                    $targetAction,
                    $this->getEntityDescription($entityClass, $isCollection)
                );
            }
            if ($description) {
                $definition->setDescription($description);
            }
        }

        $this->setDocumentationForEntity(
            $definition,
            $requestType,
            $entityClass,
            $isInherit,
            $targetAction,
            $isCollection,
            $associationName,
            $parentEntityClass
        );
    }

    private function setDocumentationForEntity(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $entityClass,
        bool $isInherit,
        string $targetAction,
        bool $isCollection,
        ?string $associationName,
        ?string $parentEntityClass
    ): void {
        $this->registerDocumentationResources($definition, $requestType);
        $this->loadDocumentationForEntity(
            $definition,
            $requestType,
            $entityClass,
            $isInherit,
            $targetAction,
            $associationName,
            $parentEntityClass
        );
        $processInheritDoc = !$associationName;
        if (!$definition->hasDocumentation()) {
            if ($associationName) {
                $this->setDocumentationForSubresource(
                    $definition,
                    $this->getAssociationDescription($associationName),
                    $targetAction,
                    $isCollection
                );
            } else {
                $processInheritDoc = false;
                $this->setDocumentationForResource(
                    $definition,
                    $targetAction,
                    $this->getEntityDescription($entityClass, false),
                    $this->getEntityDescription($entityClass, true)
                );
            }
        }
        if ($processInheritDoc) {
            $this->processInheritDocForEntity($definition, $entityClass);
        }

        $documentation = $definition->getDocumentation();
        if ($documentation) {
            if (InheritDocUtil::hasDescriptionInheritDoc($documentation)) {
                $documentation = InheritDocUtil::replaceDescriptionInheritDoc(
                    $documentation,
                    $this->entityDescriptionProvider->getEntityDocumentation($entityClass)
                );
            }
            $documentation = $this->descriptionProcessor->process($documentation, $requestType);

            $maxResultNote = $this->getMaxResultNoteForEntityDocumentation($definition, $targetAction);
            if ($maxResultNote) {
                $documentation .= $maxResultNote;
            }

            $additionalNote = $this->getUpsertAndValidateNoteForEntityDocumentation($definition, $targetAction);
            if ($additionalNote) {
                $documentation .= $additionalNote;
            }

            $definition->setDocumentation($documentation);
        }
    }

    private function getMaxResultNoteForEntityDocumentation(
        EntityDefinitionConfig $definition,
        string $targetAction
    ): ?string {
        if (ApiAction::GET_LIST === $targetAction
            || ApiAction::GET_RELATIONSHIP === $targetAction
            || ApiAction::GET_SUBRESOURCE === $targetAction
        ) {
            $maxResults = $this->getMaxResultsForEntity($definition, $this->maxEntitiesLimit);
            if (null !== $maxResults) {
                return $this->buildMaxResultNote(self::GET_LIST_MAX_RESULTS_DESCRIPTION, $maxResults);
            }
        } elseif (ApiAction::DELETE_LIST === $targetAction) {
            $maxResults = $this->getMaxResultsForEntity($definition, $this->maxDeleteEntitiesLimit);
            if (null !== $maxResults) {
                return $this->buildMaxResultNote(self::DELETE_LIST_MAX_RESULTS_DESCRIPTION, $maxResults);
            }
        }

        return null;
    }

    private function getMaxResultsForEntity(EntityDefinitionConfig $definition, int $defaultValue): ?int
    {
        $maxResults = $definition->getMaxResults() ?? $defaultValue;
        if (null === $maxResults || -1 === $maxResults) {
            return null;
        }

        return $maxResults;
    }

    private function buildMaxResultNote(string $noteTemplate, int $maxResults): string
    {
        return str_replace('{max_results}', (string)$maxResults, $noteTemplate);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getUpsertAndValidateNoteForEntityDocumentation(
        EntityDefinitionConfig $definition,
        string $targetAction
    ): ?string {
        if (ApiAction::CREATE !== $targetAction && ApiAction::UPDATE !== $targetAction) {
            return null;
        }

        $upsertDetails = '';
        $upsertConfig = $definition->getUpsertConfig();
        if ($upsertConfig->isEnabled()) {
            if ($upsertConfig->isAllowedById()) {
                $upsertDetails .= ' by the resource identifier';
            }
            if (ApiAction::CREATE === $targetAction) {
                $fields = $upsertConfig->getFields();
                if ($fields) {
                    if ($upsertDetails) {
                        $upsertDetails .= ' and';
                    }
                    $upsertDetails .= $this->formatUpsertFields($fields);
                } elseif ($upsertDetails) {
                    $upsertDetails .= '.</p>';
                }
            } elseif ($upsertDetails) {
                $upsertDetails .= '.</p>';
            }
        }

        $upsertOperation = '';
        if ($upsertDetails) {
            $upsertOperation .= '<a href="https://doc.oroinc.com/api/upsert-operation/" target="_blank">'
                . 'the upsert operation</a>'
                . $upsertDetails;
        }

        $validateOperation = '';
        if ($definition->isValidationEnabled()) {
            $validateOperation .= '<a href="https://doc.oroinc.com/api/validate-operation/" target="_blank">'
                . 'validate operation</a>';

            $validateOperation .= $upsertOperation ? ', and ' : '.</p>';
        }

        if (!$validateOperation && !$upsertOperation) {
            return null;
        }

        return '<p><strong>Note:</strong> This resource supports ' . $validateOperation . $upsertOperation;
    }

    private function formatUpsertFields(array $fields): string
    {
        if (\count($fields) === 1) {
            $fieldNames = $fields[0];
            $fieldNamesCount = \count($fieldNames);
            if ($fieldNamesCount === 1) {
                return sprintf(' by the "%s" field.</p>', $fieldNames[0]);
            }

            return sprintf(
                ' by the combination of "%s" and "%s" fields.</p>',
                implode('", "', \array_slice($fieldNames, 0, $fieldNamesCount - 1)),
                $fieldNames[$fieldNamesCount - 1]
            );
        }

        $fieldGroups = '';
        $hasSeveralFieldsInGroup = false;
        foreach ($fields as $fieldNames) {
            if (\count($fieldNames) === 1) {
                $fieldGroup = $fieldNames[0];
            } else {
                $fieldGroup = implode('", "', $fieldNames);
                $hasSeveralFieldsInGroup = true;
            }
            $fieldGroups .= "\n  <li>\"" . $fieldGroup . '"</li>';
        }
        $fieldGroups = "\n<ul>" . $fieldGroups . "\n</ul>";

        return $hasSeveralFieldsInGroup
            ? ' by the following groups of fields:</p>' . $fieldGroups
            : ' by the following fields:</p>' . $fieldGroups;
    }

    private function registerDocumentationResources(EntityDefinitionConfig $definition, RequestType $requestType): void
    {
        $resourceDocParser = $this->resourceDocParserProvider->getResourceDocParser($requestType);
        $documentationResources = $definition->getDocumentationResources();
        foreach ($documentationResources as $resource) {
            if (\is_string($resource) && !empty($resource)) {
                $resourceDocParser->registerDocumentationResource($resource);
            }
        }
    }

    private function processInheritDocForEntity(EntityDefinitionConfig $definition, string $entityClass): void
    {
        $documentation = $definition->getDocumentation();
        if (InheritDocUtil::hasInheritDoc($documentation)) {
            $entityDocumentation = $this->entityDescriptionProvider->getEntityDocumentation($entityClass);
            $definition->setDocumentation(InheritDocUtil::replaceInheritDoc($documentation, $entityDocumentation));
        }
    }

    private function loadDocumentationForEntity(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $entityClass,
        bool $isInherit,
        string $targetAction,
        ?string $associationName,
        ?string $parentEntityClass
    ): void {
        $documentation = null;
        if (!$definition->hasDocumentation()) {
            $documentation = $this->getDocumentationForEntity(
                $requestType,
                $entityClass,
                $targetAction,
                $associationName,
                $parentEntityClass
            );
        } elseif ($isInherit) {
            $documentation = $this->getDocumentationForEntity(
                $requestType,
                $entityClass,
                $targetAction,
                $associationName,
                $parentEntityClass
            );
            if (InheritDocUtil::hasInheritDoc($documentation)) {
                $documentation = InheritDocUtil::replaceInheritDoc($documentation, $definition->getDocumentation());
            }
        }
        if ($documentation) {
            $definition->setDocumentation($documentation);
        }
    }

    private function getDocumentationForEntity(
        RequestType $requestType,
        string $entityClass,
        string $targetAction,
        ?string $associationName,
        ?string $parentEntityClass
    ): ?string {
        $resourceDocParser = $this->resourceDocParserProvider->getResourceDocParser($requestType);
        if ($associationName) {
            return $resourceDocParser->getSubresourceDocumentation(
                $parentEntityClass,
                $associationName,
                $targetAction
            );
        }

        return $resourceDocParser->getActionDocumentation($entityClass, $targetAction);
    }

    private function setDocumentationForResource(
        EntityDefinitionConfig $definition,
        string $targetAction,
        string $entitySingularName,
        string $entityPluralName
    ): void {
        $documentation = $this->resourceDocProvider->getResourceDocumentation(
            $targetAction,
            $entitySingularName,
            $entityPluralName
        );
        if ($documentation) {
            $definition->setDocumentation($documentation);
        }
    }

    private function setDocumentationForSubresource(
        EntityDefinitionConfig $definition,
        string $associationDescription,
        string $targetAction,
        bool $isCollection
    ): void {
        $documentation = $this->resourceDocProvider->getSubresourceDocumentation(
            $targetAction,
            $associationDescription,
            $isCollection
        );
        if ($documentation) {
            $definition->setDocumentation($documentation);
        }
    }

    private function getEntityDescription(string $entityClass, bool $isCollection): string
    {
        if ($isCollection) {
            if (isset($this->pluralEntityDescriptions[$entityClass])) {
                return $this->pluralEntityDescriptions[$entityClass];
            }
        } elseif (isset($this->singularEntityDescriptions[$entityClass])) {
            return $this->singularEntityDescriptions[$entityClass];
        }

        if ($isCollection) {
            $entityDescription = $this->entityNameProvider->getEntityPluralName($entityClass);
            $this->pluralEntityDescriptions[$entityClass] = $entityDescription;
        } else {
            $entityDescription = $this->entityNameProvider->getEntityName($entityClass);
            $this->singularEntityDescriptions[$entityClass] = $entityDescription;
        }

        return $entityDescription;
    }

    private function getAssociationDescription(string $associationName): string
    {
        if (isset($this->humanizedAssociationNames[$associationName])) {
            return $this->humanizedAssociationNames[$associationName];
        }

        $humanizedAssociationName = $this->entityDescriptionProvider->humanizeAssociationName($associationName);
        $this->humanizedAssociationNames[$associationName] = $humanizedAssociationName;

        return $humanizedAssociationName;
    }

    private function trans(Label $label): ?string
    {
        return $label->trans($this->translator) ?: null;
    }
}
