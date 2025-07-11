<?php

namespace Oro\Bundle\TagBundle\Api\Processor;

use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\ResourceDocParserProvider;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds human-readable descriptions for "tags" association of taggable entities.
 */
class AddTagsAssociationDescriptions implements ProcessorInterface
{
    private const TAGS_ASSOCIATION_DOC_RESOURCE = '@OroTagBundle/Resources/doc/api/tags_association.md';
    private const TAGGABLE_ENTITY = '%taggable_entity%';
    private const TAGS_ASSOCIATION = '%tags_association%';
    private const TAG_ENTITY_TYPE = '%tag_entity_type%';
    private const ENTITY_NAME = '%entity_name%';
    private const TAGS_ASSOCIATION_NAME = 'tags';

    private TaggableHelper $taggableHelper;
    private ValueNormalizer $valueNormalizer;
    private ResourceDocParserProvider $resourceDocParserProvider;
    private EntityNameProvider $entityNameProvider;

    public function __construct(
        TaggableHelper $taggableHelper,
        ValueNormalizer $valueNormalizer,
        ResourceDocParserProvider $resourceDocParserProvider,
        EntityNameProvider $entityNameProvider
    ) {
        $this->taggableHelper = $taggableHelper;
        $this->resourceDocParserProvider = $resourceDocParserProvider;
        $this->valueNormalizer = $valueNormalizer;
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

        $requestType = $context->getRequestType();
        $definition = $context->getResult();
        $this->setDescriptionsForFields($definition, $requestType, $context->getClassName());
        $associationName = $context->getAssociationName();
        if ($associationName
            && self::TAGS_ASSOCIATION_NAME === $associationName
            && !$definition->hasDocumentation()
        ) {
            $this->setDescriptionsForSubresource(
                $definition,
                $requestType,
                $context->getParentClassName(),
                $targetAction
            );
        }
    }

    private function setDescriptionsForFields(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $entityClass
    ): void {
        if (!$this->taggableHelper->isTaggable($entityClass)) {
            return;
        }

        $docParser = $this->getDocumentationParser($requestType, self::TAGS_ASSOCIATION_DOC_RESOURCE);
        $associationDocumentationTemplate = $docParser->getFieldDocumentation(
            self::TAGGABLE_ENTITY,
            self::TAGS_ASSOCIATION
        );

        $entityName = $this->entityNameProvider->getEntityName($entityClass, true);
        $tagsAssociationDefinition = $definition->getField(self::TAGS_ASSOCIATION_NAME);
        if (null !== $tagsAssociationDefinition && !$tagsAssociationDefinition->hasDescription()) {
            $tagsAssociationDefinition->setDescription(strtr($associationDocumentationTemplate, [
                self::ENTITY_NAME => $entityName
            ]));
        }
    }

    private function setDescriptionsForSubresource(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $entityClass,
        string $targetAction
    ): void {
        if (!$this->taggableHelper->isTaggable($entityClass)) {
            return;
        }

        $docParser = $this->getDocumentationParser($requestType, self::TAGS_ASSOCIATION_DOC_RESOURCE);
        $subresourceDocumentationTemplate = $docParser->getSubresourceDocumentation(
            self::TAGGABLE_ENTITY,
            self::TAGS_ASSOCIATION,
            $targetAction
        );

        $definition->setDocumentation(strtr($subresourceDocumentationTemplate, [
            self::ENTITY_NAME => $this->entityNameProvider->getEntityName($entityClass, true),
            self::TAG_ENTITY_TYPE => $this->getEntityType(Tag::class, $requestType)
        ]));
    }

    private function getEntityType(string $entityClass, RequestType $requestType): string
    {
        return ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $entityClass, $requestType);
    }

    private function getDocumentationParser(
        RequestType $requestType,
        string $documentationResource
    ): ResourceDocParserInterface {
        $docParser = $this->resourceDocParserProvider->getResourceDocParser($requestType);
        $docParser->registerDocumentationResource($documentationResource);

        return $docParser;
    }
}
