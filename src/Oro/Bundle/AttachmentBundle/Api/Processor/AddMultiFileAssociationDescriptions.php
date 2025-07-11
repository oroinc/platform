<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\FieldDescriptionUtil;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\ResourceDocParserProvider;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\InheritDocUtil;
use Oro\Bundle\AttachmentBundle\Api\MultiFileAssociationProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds human-readable descriptions for multi files and multi images associations.
 */
class AddMultiFileAssociationDescriptions implements ProcessorInterface
{
    private const string MULTI_FILE_ASSOCIATION_DOC_RESOURCE =
        '@OroAttachmentBundle/Resources/doc/api/multi_file_association.md';
    private const string MULTI_FILE_TARGET_ENTITY = '%multi_file_target_entity%';
    private const string MULTI_FILE_ASSOCIATION = '%multi_file_association%';
    private const string ASSOCIATION = '%association%';
    private const string ENTITY_NAME = '%entity_name%';
    private const string SORT_ORDER = 'sortOrder';
    private const string SORT_ORDER_NONE =
        'The <code>sortOrder</code> meta option denotes which order a file is to appear when displayed.';

    private EntityDescriptionProvider $entityDescriptionProvider;
    private EntityNameProvider $entityNameProvider;

    public function __construct(
        private readonly MultiFileAssociationProvider $multiFileAssociationProvider,
        private readonly ResourceDocParserProvider $resourceDocParserProvider
    ) {
    }

    public function setEntityDescriptionProvider(EntityDescriptionProvider $entityDescriptionProvider): void
    {
        $this->entityDescriptionProvider = $entityDescriptionProvider;
    }

    public function setEntityNameProvider(EntityNameProvider $entityNameProvider): void
    {
        $this->entityNameProvider = $entityNameProvider;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $targetAction = $context->getTargetAction();
        if (!$targetAction || ApiAction::OPTIONS === $targetAction) {
            return;
        }

        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $definition = $context->getResult();
        $this->setDescriptionsForMultiFileFields(
            $definition,
            $version,
            $requestType,
            $context->getClassName(),
            $targetAction
        );
        if ($context->getAssociationName()) {
            $this->setDescriptionsForMultiFileSubresources(
                $definition,
                $version,
                $requestType,
                $context->getParentClassName(),
                $targetAction
            );
        }
    }

    private function setDescriptionsForMultiFileFields(
        EntityDefinitionConfig $definition,
        string $version,
        RequestType $requestType,
        string $entityClass,
        string $targetAction
    ): void {
        $multiFileAssociationNames = $this->multiFileAssociationProvider->getMultiFileAssociationNames(
            $entityClass,
            $version,
            $requestType
        );
        if (!$multiFileAssociationNames) {
            return;
        }

        $docParser = $this->getDocumentationParser($requestType, self::MULTI_FILE_ASSOCIATION_DOC_RESOURCE);
        foreach ($multiFileAssociationNames as $associationName) {
            $associationDefinition = $definition->getField($associationName);
            if (null !== $associationDefinition) {
                $associationDocumentationTemplate = $this->getAssociationDocumentationTemplate(
                    $docParser,
                    self::MULTI_FILE_TARGET_ENTITY,
                    self::MULTI_FILE_ASSOCIATION,
                    $targetAction
                );
                $associationDocumentation = InheritDocUtil::replaceInheritDoc(
                    $associationDocumentationTemplate,
                    $definition->findFieldByPath($associationName, true)?->getDescription()
                );
                $hasSortOrder = $associationDefinition->getTargetEntity()
                    ?->hasField(ConfigUtil::buildMetaPropertyName(self::SORT_ORDER));
                if ($hasSortOrder) {
                    $associationDocumentation = FieldDescriptionUtil::addFieldNote(
                        $associationDocumentation,
                        self::SORT_ORDER_NONE
                    );
                }
                if (false === $associationDefinition->getFormOption('mapped')
                    && (ApiAction::CREATE === $targetAction || ApiAction::UPDATE === $targetAction)
                ) {
                    $associationDocumentation = FieldDescriptionUtil::addReadOnlyFieldNote($associationDocumentation);
                }
                $associationDefinition->setDescription($associationDocumentation);
            }
        }
    }

    private function setDescriptionsForMultiFileSubresources(
        EntityDefinitionConfig $definition,
        string $version,
        RequestType $requestType,
        string $entityClass,
        string $targetAction
    ): void {
        $multiFileAssociationNames = $this->multiFileAssociationProvider->getMultiFileAssociationNames(
            $entityClass,
            $version,
            $requestType
        );
        if (!$multiFileAssociationNames) {
            return;
        }

        $docParser = $this->getDocumentationParser($requestType, self::MULTI_FILE_ASSOCIATION_DOC_RESOURCE);
        $subresourceDocumentationTemplate = $docParser->getSubresourceDocumentation(
            self::MULTI_FILE_TARGET_ENTITY,
            self::MULTI_FILE_ASSOCIATION,
            $targetAction
        );
        $entityName = $this->entityNameProvider->getEntityName($entityClass, true);
        foreach ($multiFileAssociationNames as $associationName) {
            $definition->setDocumentation(strtr($subresourceDocumentationTemplate, [
                self::ASSOCIATION => $this->entityDescriptionProvider->getFieldDescription(
                    $entityClass,
                    $associationName
                ),
                self::ENTITY_NAME => $entityName
            ]));
        }
    }

    private function getDocumentationParser(
        RequestType $requestType,
        string $documentationResource
    ): ResourceDocParserInterface {
        $docParser = $this->resourceDocParserProvider->getResourceDocParser($requestType);
        $docParser->registerDocumentationResource($documentationResource);

        return $docParser;
    }

    private function getAssociationDocumentationTemplate(
        ResourceDocParserInterface $docParser,
        string $className,
        string $fieldName,
        string $targetAction
    ): ?string {
        return $docParser->getFieldDocumentation($className, $fieldName, $targetAction)
            ?: $docParser->getFieldDocumentation($className, $fieldName);
    }
}
