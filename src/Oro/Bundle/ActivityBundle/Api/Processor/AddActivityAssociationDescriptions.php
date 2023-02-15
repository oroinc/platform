<?php

namespace Oro\Bundle\ActivityBundle\Api\Processor;

use Oro\Bundle\ActivityBundle\Api\ActivityAssociationProvider;
use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\ResourceDocParserProvider;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds human-readable descriptions for associations with activity entities.
 */
class AddActivityAssociationDescriptions implements ProcessorInterface
{
    private const ACTIVITY_TARGETS_ASSOCIATION_NAME = 'activityTargets';

    private const ACTIVITY_TARGETS_ASSOCIATION_DOC_RESOURCE =
        '@OroActivityBundle/Resources/doc/api/activity_targets_association.md';
    private const ACTIVITY_ENTITY = '%activity_entity%';
    private const ACTIVITY_TARGETS_ASSOCIATION = '%activity_targets_association%';

    private const ACTIVITY_ASSOCIATION_DOC_RESOURCE =
        '@OroActivityBundle/Resources/doc/api/activity_association.md';
    private const ACTIVITY_TARGET_ENTITY = '%activity_target_entity%';
    private const ACTIVITY_ASSOCIATION = '%activity_association%';

    private ActivityAssociationProvider $activityAssociationProvider;
    private ValueNormalizer $valueNormalizer;
    private ResourceDocParserProvider $resourceDocParserProvider;
    private EntityNameProvider $entityNameProvider;

    public function __construct(
        ActivityAssociationProvider $activityAssociationProvider,
        ValueNormalizer $valueNormalizer,
        ResourceDocParserProvider $resourceDocParserProvider,
        EntityNameProvider $entityNameProvider
    ) {
        $this->activityAssociationProvider = $activityAssociationProvider;
        $this->resourceDocParserProvider = $resourceDocParserProvider;
        $this->valueNormalizer = $valueNormalizer;
        $this->entityNameProvider = $entityNameProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $targetAction = $context->getTargetAction();
        if (!$targetAction || ApiAction::OPTIONS === $targetAction) {
            return;
        }

        $associationName = $context->getAssociationName();
        $entityClass = $associationName ? $context->getParentClassName() : $context->getClassName();
        $version = $context->getVersion();
        $requestType = $context->getRequestType();

        if ($this->activityAssociationProvider->isActivityEntity($entityClass)) {
            $this->addActivityTargetsAssociationDescription(
                $context->getResult(),
                $version,
                $requestType,
                $targetAction,
                $entityClass,
                $associationName,
            );
        }

        $activityAssociations = $this->activityAssociationProvider->getActivityAssociations(
            $entityClass,
            $version,
            $requestType
        );
        if ($activityAssociations) {
            $this->addActivityAssociationDescriptions(
                $context->getResult(),
                $requestType,
                $targetAction,
                $entityClass,
                $associationName,
                $activityAssociations
            );
        }
    }

    private function addActivityTargetsAssociationDescription(
        EntityDefinitionConfig $definition,
        string $version,
        RequestType $requestType,
        string $targetAction,
        string $activityEntityClass,
        ?string $associationName
    ): void {
        if (!$associationName) {
            $this->setDescriptionForActivityTargetsField(
                $definition,
                $requestType,
                $activityEntityClass
            );
        } elseif (self::ACTIVITY_TARGETS_ASSOCIATION_NAME === $associationName && !$definition->hasDocumentation()) {
            $this->setDescriptionsForActivityTargetsSubresource(
                $definition,
                $version,
                $requestType,
                $activityEntityClass,
                $targetAction
            );
        }
    }

    private function addActivityAssociationDescriptions(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $targetAction,
        string $entityClass,
        ?string $associationName,
        array $activityAssociations
    ): void {
        if (!$associationName) {
            $this->setDescriptionsForFields(
                $definition,
                $requestType,
                $entityClass,
                $activityAssociations
            );
        } elseif (isset($activityAssociations[$associationName]) && !$definition->hasDocumentation()) {
            $this->setDescriptionsForSubresource(
                $definition,
                $requestType,
                $entityClass,
                $activityAssociations[$associationName]['className'],
                $targetAction
            );
        }
    }

    private function setDescriptionForActivityTargetsField(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $activityEntityClass
    ): void {
        $activityTargetsAssociationDefinition = $definition->getField(self::ACTIVITY_TARGETS_ASSOCIATION_NAME);
        if (null === $activityTargetsAssociationDefinition
            || $activityTargetsAssociationDefinition->hasDescription()
        ) {
            return;
        }

        $docParser = $this->getDocumentationParser($requestType, self::ACTIVITY_TARGETS_ASSOCIATION_DOC_RESOURCE);
        $associationDocumentationTemplate = $docParser->getFieldDocumentation(
            self::ACTIVITY_ENTITY,
            self::ACTIVITY_TARGETS_ASSOCIATION
        );
        $activityTargetsAssociationDefinition->setDescription(strtr($associationDocumentationTemplate, [
            '%activity_entity_name%' => $this->entityNameProvider->getEntityName($activityEntityClass, true)
        ]));
    }

    private function setDescriptionsForActivityTargetsSubresource(
        EntityDefinitionConfig $definition,
        string $version,
        RequestType $requestType,
        string $activityEntityClass,
        string $targetAction
    ): void {
        $docParser = $this->getDocumentationParser($requestType, self::ACTIVITY_TARGETS_ASSOCIATION_DOC_RESOURCE);
        $subresourceDocumentationTemplate = $docParser->getSubresourceDocumentation(
            self::ACTIVITY_ENTITY,
            self::ACTIVITY_TARGETS_ASSOCIATION,
            $targetAction
        );
        $activityTargetEntityClasses = $this->activityAssociationProvider->getActivityTargetClasses(
            $activityEntityClass,
            $version,
            $requestType
        );
        $activityTargetEntityType = $activityTargetEntityClasses
            ? $this->getEntityType(reset($activityTargetEntityClasses), $requestType)
            : 'users';
        $definition->setDocumentation(strtr($subresourceDocumentationTemplate, [
            '%activity_entity_name%'        => $this->entityNameProvider->getEntityName($activityEntityClass, true),
            '%activity_target_entity_type%' => $activityTargetEntityType
        ]));
    }

    private function setDescriptionsForFields(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $entityClass,
        array $activityAssociations
    ): void {
        $docParser = $this->getDocumentationParser($requestType, self::ACTIVITY_ASSOCIATION_DOC_RESOURCE);
        $associationDocumentationTemplate = $docParser->getFieldDocumentation(
            self::ACTIVITY_TARGET_ENTITY,
            self::ACTIVITY_ASSOCIATION
        );

        $entityName = $this->entityNameProvider->getEntityName($entityClass, true);
        foreach ($activityAssociations as $associationName => $activityAssociation) {
            $activityAssociationDefinition = $definition->getField($associationName);
            if (null === $activityAssociationDefinition || $activityAssociationDefinition->hasDescription()) {
                continue;
            }
            $activityAssociationDefinition->setDescription(strtr($associationDocumentationTemplate, [
                '%entity_name%'                 => $entityName,
                '%activity_entity_plural_name%' => $this->entityNameProvider->getEntityPluralName(
                    $activityAssociation['className'],
                    true
                )
            ]));
        }
    }

    private function setDescriptionsForSubresource(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $entityClass,
        string $activityEntityClass,
        string $targetAction
    ): void {
        $docParser = $this->getDocumentationParser($requestType, self::ACTIVITY_ASSOCIATION_DOC_RESOURCE);
        $subresourceDocumentationTemplate = $docParser->getSubresourceDocumentation(
            self::ACTIVITY_TARGET_ENTITY,
            self::ACTIVITY_ASSOCIATION,
            $targetAction
        );

        $definition->setDocumentation(strtr($subresourceDocumentationTemplate, [
            '%entity_name%'                 => $this->entityNameProvider->getEntityName($entityClass, true),
            '%activity_entity_plural_name%' => $this->entityNameProvider->getEntityPluralName(
                $activityEntityClass,
                true
            ),
            '%activity_entity_type%'        => $this->getEntityType($activityEntityClass, $requestType)
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
