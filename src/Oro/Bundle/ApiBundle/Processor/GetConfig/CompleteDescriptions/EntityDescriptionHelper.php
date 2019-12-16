<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProvider;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\InheritDocUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The helper that is used to set descriptions of entities.
 */
class EntityDescriptionHelper
{
    /** @var EntityDescriptionProvider */
    private $entityDocProvider;

    /** @var TranslatorInterface */
    private $translator;

    /** @var ResourceDocProvider */
    private $resourceDocProvider;

    /** @var ResourceDocParserProvider */
    private $resourceDocParserProvider;

    /** @var DescriptionProcessor */
    private $descriptionProcessor;

    /** @var IdentifierDescriptionHelper */
    private $identifierDescriptionHelper;

    /**
     * @param EntityDescriptionProvider   $entityDocProvider
     * @param TranslatorInterface         $translator
     * @param ResourceDocProvider         $resourceDocProvider
     * @param ResourceDocParserProvider   $resourceDocParserProvider
     * @param DescriptionProcessor        $descriptionProcessor
     * @param IdentifierDescriptionHelper $identifierDescriptionHelper
     */
    public function __construct(
        EntityDescriptionProvider $entityDocProvider,
        TranslatorInterface $translator,
        ResourceDocProvider $resourceDocProvider,
        ResourceDocParserProvider $resourceDocParserProvider,
        DescriptionProcessor $descriptionProcessor,
        IdentifierDescriptionHelper $identifierDescriptionHelper
    ) {
        $this->entityDocProvider = $entityDocProvider;
        $this->translator = $translator;
        $this->resourceDocProvider = $resourceDocProvider;
        $this->resourceDocParserProvider = $resourceDocParserProvider;
        $this->descriptionProcessor = $descriptionProcessor;
        $this->identifierDescriptionHelper = $identifierDescriptionHelper;
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param RequestType            $requestType
     * @param string                 $entityClass
     * @param bool                   $isInherit
     * @param string                 $targetAction
     * @param bool                   $isCollection
     * @param string|null            $associationName
     * @param string|null            $parentEntityClass
     */
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

        $entityDescription = null;
        if ($isInherit || !$definition->hasDescription()) {
            if ($associationName) {
                $entityDescription = $this->getAssociationDescription($associationName);
            } else {
                $entityDescription = $this->getEntityDescription($entityClass, $isCollection);
            }
        }
        if ($definition->hasDescription()) {
            $description = $definition->getDescription();
            if ($description instanceof Label) {
                $definition->setDescription($this->trans($description));
            }
        } else {
            if ($associationName) {
                $description = $this->resourceDocProvider->getSubresourceDescription(
                    $targetAction,
                    $entityDescription,
                    $isCollection
                );
            } else {
                $description = $this->resourceDocProvider->getResourceDescription(
                    $targetAction,
                    $entityDescription
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
            $parentEntityClass,
            $entityDescription
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param RequestType            $requestType
     * @param string                 $entityClass
     * @param bool                   $isInherit
     * @param string                 $targetAction
     * @param bool                   $isCollection
     * @param string|null            $associationName
     * @param string|null            $parentEntityClass
     * @param string|null            $entityDescription
     */
    private function setDocumentationForEntity(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $entityClass,
        bool $isInherit,
        string $targetAction,
        bool $isCollection,
        ?string $associationName,
        ?string $parentEntityClass,
        ?string $entityDescription
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
                if (!$entityDescription) {
                    $entityDescription = $this->getAssociationDescription($associationName);
                }
                $this->setDocumentationForSubresource($definition, $entityDescription, $targetAction, $isCollection);
            } else {
                $processInheritDoc = false;
                if (!$entityDescription) {
                    $entityDescription = $this->getEntityDescription($entityClass, $isCollection);
                }
                $this->setDocumentationForResource($definition, $targetAction, $entityDescription);
            }
        }
        if ($processInheritDoc) {
            $this->processInheritDocForEntity($definition, $entityClass);
        }

        $documentation = $definition->getDocumentation();
        if ($documentation) {
            $definition->setDocumentation($this->descriptionProcessor->process($documentation, $requestType));
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param RequestType            $requestType
     */
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

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    private function processInheritDocForEntity(EntityDefinitionConfig $definition, string $entityClass): void
    {
        $documentation = $definition->getDocumentation();
        if (InheritDocUtil::hasInheritDoc($documentation)) {
            $entityDocumentation = $this->entityDocProvider->getEntityDocumentation($entityClass);
            $definition->setDocumentation(InheritDocUtil::replaceInheritDoc($documentation, $entityDocumentation));
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param RequestType            $requestType
     * @param string                 $entityClass
     * @param bool                   $isInherit
     * @param string                 $targetAction
     * @param string|null            $associationName
     * @param string|null            $parentEntityClass
     */
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

    /**
     * @param RequestType $requestType
     * @param string      $entityClass
     * @param string      $targetAction
     * @param string|null $associationName
     * @param string|null $parentEntityClass
     *
     * @return string|null
     */
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

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $targetAction
     * @param string                 $entityDescription
     */
    private function setDocumentationForResource(
        EntityDefinitionConfig $definition,
        string $targetAction,
        string $entityDescription
    ): void {
        $documentation = $this->resourceDocProvider->getResourceDocumentation($targetAction, $entityDescription);
        if ($documentation) {
            $definition->setDocumentation($documentation);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $associationDescription
     * @param string                 $targetAction
     * @param bool                   $isCollection
     */
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

    /**
     * @param string $entityClass
     * @param bool   $isCollection
     *
     * @return string
     */
    private function getEntityDescription(string $entityClass, bool $isCollection): string
    {
        $entityDescription = $isCollection
            ? $this->entityDocProvider->getEntityPluralDescription($entityClass)
            : $this->entityDocProvider->getEntityDescription($entityClass);
        if (!$entityDescription) {
            $lastDelimiter = \strrpos($entityClass, '\\');
            if (false !== $lastDelimiter) {
                $entityClass = \substr($entityClass, $lastDelimiter + 1);
            }
            // convert "SomeClassName" to "Some Class Name".
            $entityDescription = preg_replace('~(?<=\\w)([A-Z])~', ' $1', $entityClass);
            if ($isCollection) {
                $entityDescription = Inflector::pluralize($entityDescription);
            }
        }

        return $entityDescription;
    }

    /**
     * @param string $associationName
     *
     * @return string
     */
    private function getAssociationDescription(string $associationName): string
    {
        return $this->entityDocProvider->humanizeAssociationName($associationName);
    }

    /**
     * @param Label $label
     *
     * @return string|null
     */
    private function trans(Label $label): ?string
    {
        return $label->trans($this->translator) ?: null;
    }
}
