<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProvider;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\InheritDocUtil;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The helper that is used to set descriptions of entities.
 */
class EntityDescriptionHelper implements ResetInterface
{
    private EntityDescriptionProvider $entityDescriptionProvider;
    private EntityNameProvider $entityNameProvider;
    private TranslatorInterface $translator;
    private ResourceDocProvider $resourceDocProvider;
    private ResourceDocParserProvider $resourceDocParserProvider;
    private DescriptionProcessor $descriptionProcessor;
    private IdentifierDescriptionHelper $identifierDescriptionHelper;

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
        IdentifierDescriptionHelper $identifierDescriptionHelper
    ) {
        $this->entityDescriptionProvider = $entityDescriptionProvider;
        $this->entityNameProvider = $entityNameProvider;
        $this->translator = $translator;
        $this->resourceDocProvider = $resourceDocProvider;
        $this->resourceDocParserProvider = $resourceDocParserProvider;
        $this->descriptionProcessor = $descriptionProcessor;
        $this->identifierDescriptionHelper = $identifierDescriptionHelper;
    }

    /**
     * {@inheritDoc}
     */
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
            $definition->setDocumentation($this->descriptionProcessor->process($documentation, $requestType));
        }
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
