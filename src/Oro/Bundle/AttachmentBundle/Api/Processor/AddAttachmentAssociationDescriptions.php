<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\ResourceDocParserProvider;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\AttachmentBundle\Api\AttachmentAssociationProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds human-readable descriptions for associations with the attachment entity.
 */
class AddAttachmentAssociationDescriptions implements ProcessorInterface
{
    private const ATTACHMENTS_ASSOCIATION_NAME = 'attachments';

    private const ATTACHMENT_ASSOCIATION_DOC_RESOURCE =
        '@OroAttachmentBundle/Resources/doc/api/attachment_association.md';
    private const ATTACHMENT_TARGET_ENTITY = '%attachment_target_entity%';
    private const ATTACHMENTS_ASSOCIATION = '%attachments_association%';
    private const ENTITY_NAME = '%entity_name%';

    private AttachmentAssociationProvider $attachmentAssociationProvider;
    private ResourceDocParserProvider $resourceDocParserProvider;
    private EntityNameProvider $entityNameProvider;

    public function __construct(
        AttachmentAssociationProvider $attachmentAssociationProvider,
        ResourceDocParserProvider $resourceDocParserProvider,
        EntityNameProvider $entityNameProvider
    ) {
        $this->attachmentAssociationProvider = $attachmentAssociationProvider;
        $this->resourceDocParserProvider = $resourceDocParserProvider;
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
        $this->setDescriptionsForAttachmentsField(
            $definition,
            $version,
            $requestType,
            $context->getClassName(),
            $targetAction
        );
        $associationName = $context->getAssociationName();
        if ($associationName
            && self::ATTACHMENTS_ASSOCIATION_NAME === $associationName
            && !$definition->hasDocumentation()
        ) {
            $this->setDescriptionsForSubresource(
                $definition,
                $version,
                $requestType,
                $context->getParentClassName(),
                $targetAction
            );
        }
    }

    private function setDescriptionsForAttachmentsField(
        EntityDefinitionConfig $definition,
        string $version,
        RequestType $requestType,
        string $entityClass,
        string $targetAction
    ): void {
        if (!$this->attachmentAssociationProvider->getAttachmentAssociationName($entityClass, $version, $requestType)) {
            return;
        }

        $attachmentsAssociationDefinition = $definition->getField(self::ATTACHMENTS_ASSOCIATION_NAME);
        if (null === $attachmentsAssociationDefinition || $attachmentsAssociationDefinition->hasDescription()) {
            return;
        }

        $associationDocumentationTemplate = $this->getAssociationDocumentationTemplate(
            $this->getDocumentationParser($requestType, self::ATTACHMENT_ASSOCIATION_DOC_RESOURCE),
            self::ATTACHMENT_TARGET_ENTITY,
            self::ATTACHMENTS_ASSOCIATION,
            $targetAction
        );
        $attachmentsAssociationDefinition->setDescription(strtr($associationDocumentationTemplate, [
            self::ENTITY_NAME => $this->entityNameProvider->getEntityName($entityClass, true)
        ]));
    }

    private function setDescriptionsForSubresource(
        EntityDefinitionConfig $definition,
        string $version,
        RequestType $requestType,
        string $entityClass,
        string $targetAction
    ): void {
        if (!$this->attachmentAssociationProvider->getAttachmentAssociationName($entityClass, $version, $requestType)) {
            return;
        }

        $docParser = $this->getDocumentationParser($requestType, self::ATTACHMENT_ASSOCIATION_DOC_RESOURCE);
        $subresourceDocumentationTemplate = $docParser->getSubresourceDocumentation(
            self::ATTACHMENT_TARGET_ENTITY,
            self::ATTACHMENTS_ASSOCIATION,
            $targetAction
        );
        $definition->setDocumentation(strtr($subresourceDocumentationTemplate, [
            self::ENTITY_NAME => $this->entityNameProvider->getEntityName($entityClass, true)
        ]));
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
