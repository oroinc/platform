<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * The base class for processors that prepare JSON:API request data to be processed by Symfony Forms.
 */
abstract class AbstractNormalizeRequestData implements ProcessorInterface
{
    protected const ROOT_POINTER = '';

    protected ValueNormalizer $valueNormalizer;
    protected EntityIdTransformerRegistry $entityIdTransformerRegistry;
    protected ?FormContext $context = null;

    public function __construct(
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerRegistry $entityIdTransformerRegistry
    ) {
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    protected function normalizeData(string $path, string $pointer, array $data, ?EntityMetadata $metadata): array
    {
        $relations = \array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)
            ? $this->normalizeRelationships($path, $pointer, $data[JsonApiDoc::RELATIONSHIPS], $metadata)
            : [];

        $result = !empty($data[JsonApiDoc::ATTRIBUTES])
            ? array_merge($data[JsonApiDoc::ATTRIBUTES], $relations)
            : $relations;

        if (null !== $metadata && !empty($data[JsonApiDoc::META])) {
            foreach ($data[JsonApiDoc::META] as $name => $value) {
                if ($metadata->hasMetaProperty($name)) {
                    $result[$name] = $value;
                }
            }
        }

        return $result;
    }

    protected function normalizeRelationships(
        string $path,
        string $pointer,
        array $relationships,
        ?EntityMetadata $metadata
    ): array {
        $relations = [];
        $relationshipsPointer = $this->buildPointer($pointer, JsonApiDoc::RELATIONSHIPS);
        foreach ($relationships as $name => $value) {
            $relationshipsDataItemPath = $this->buildPath($path, $name);
            $relationshipsDataItemPointer = $this->buildPointer(
                $this->buildPointer($relationshipsPointer, $name),
                JsonApiDoc::DATA
            );
            $relationData = $value[JsonApiDoc::DATA];

            // Relation data can be null in case to-one and an empty array in case to-many relation.
            // In this case we should process this relation data as empty relation
            if (empty($relationData)) {
                $relations[$name] = [];
                continue;
            }

            $associationMetadata = $metadata?->getAssociation($name);
            if (ArrayUtil::isAssoc($relationData)) {
                $relations[$name] = $this->normalizeRelationshipItem(
                    $relationshipsDataItemPath,
                    $relationshipsDataItemPointer,
                    $relationData,
                    $associationMetadata
                );
            } else {
                foreach ($relationData as $key => $collectionItem) {
                    $relations[$name][] = $this->normalizeRelationshipItem(
                        $this->buildPath($relationshipsDataItemPath, $key),
                        $this->buildPointer($relationshipsDataItemPointer, $key),
                        $collectionItem,
                        $associationMetadata
                    );
                }
            }
        }

        return $relations;
    }

    /**
     * @param string                   $path
     * @param string                   $pointer
     * @param array                    $data
     * @param AssociationMetadata|null $associationMetadata
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    protected function normalizeRelationshipItem(
        string $path,
        string $pointer,
        array $data,
        ?AssociationMetadata $associationMetadata
    ): array {
        $entityClass = $this->normalizeEntityClass(
            $this->buildPointer($pointer, JsonApiDoc::TYPE),
            $data[JsonApiDoc::TYPE]
        );
        $entityId = $data[JsonApiDoc::ID];
        if (str_contains($entityClass, '\\')) {
            if ($this->isAcceptableTargetClass($entityClass, $associationMetadata)) {
                $targetMetadata = $associationMetadata?->getTargetMetadata();
                $entityId = $this->normalizeEntityId(
                    $this->buildPath($path, 'id'),
                    $this->buildPointer($pointer, JsonApiDoc::ID),
                    $entityClass,
                    $entityId,
                    $targetMetadata
                );
            } else {
                $this->addValidationError(Constraint::ENTITY_TYPE, $this->buildPointer($pointer, JsonApiDoc::TYPE))
                    ->setDetail('Not acceptable entity type.');
            }
        }

        return [
            'class' => $entityClass,
            'id'    => $entityId
        ];
    }

    protected function isAcceptableTargetClass(string $entityClass, ?AssociationMetadata $associationMetadata): bool
    {
        if (null === $associationMetadata) {
            return true;
        }

        $acceptableClassNames = $associationMetadata->getAcceptableTargetClassNames();
        if (empty($acceptableClassNames)) {
            return $associationMetadata->isEmptyAcceptableTargetsAllowed();
        }

        return \in_array($entityClass, $acceptableClassNames, true);
    }

    protected function normalizeEntityClass(string $pointer, string $entityType): string
    {
        $entityClass = ValueNormalizerUtil::tryConvertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->context->getRequestType()
        );
        if (null === $entityClass) {
            $this->addValidationError(Constraint::ENTITY_TYPE, $pointer)
                ->setDetail(sprintf('Unknown entity type: %s.', $entityType));
            $entityClass = $entityType;
        }

        return $entityClass;
    }

    protected function normalizeEntityId(
        string $path,
        string $pointer,
        string $entityClass,
        mixed $entityId,
        ?EntityMetadata $metadata
    ): mixed {
        // keep the id of the primary and an included entity as is
        $includedEntities = $this->context->getIncludedEntities();
        if (null !== $includedEntities
            && (
                $includedEntities->isPrimaryEntity($entityClass, $entityId)
                || null !== $includedEntities->get($entityClass, $entityId)
            )
        ) {
            return $entityId;
        }

        // keep the id as is if the entity metadata is undefined
        if (null === $metadata) {
            return $entityId;
        }

        try {
            $normalizedId = $this->getEntityIdTransformer($this->context->getRequestType())
                ->reverseTransform($entityId, $metadata);
            if (null === $normalizedId) {
                $this->context->addNotResolvedIdentifier(
                    'requestData' . ConfigUtil::PATH_DELIMITER . $path,
                    new NotResolvedIdentifier($entityId, $entityClass)
                );
            }

            return $normalizedId;
        } catch (\Exception $e) {
            $this->addValidationError(Constraint::ENTITY_ID, $pointer)
                ->setInnerException($e);
        }

        return $entityId;
    }

    protected function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }

    protected function addValidationError(string $title, string $pointer = null): Error
    {
        $error = Error::createValidationError($title);
        if (null !== $pointer) {
            $error->setSource(ErrorSource::createByPointer($pointer));
        }
        $this->context->addError($error);

        return $error;
    }

    protected function buildPath(string $parentPath, string $property): string
    {
        return '' !== $parentPath
            ? $parentPath . ConfigUtil::PATH_DELIMITER . $property
            : $property;
    }

    protected function buildPointer(string $parentPointer, string $property): string
    {
        return $parentPointer . '/' . $property;
    }
}
