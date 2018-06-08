<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * The base class for processors that prepare JSON.API request data to be processed by Symfony Forms.
 */
abstract class AbstractNormalizeRequestData implements ProcessorInterface
{
    protected const ROOT_POINTER = '';

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerRegistry */
    protected $entityIdTransformerRegistry;

    /** @var FormContext */
    protected $context;

    /**
     * @param ValueNormalizer             $valueNormalizer
     * @param EntityIdTransformerRegistry $entityIdTransformerRegistry
     */
    public function __construct(
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerRegistry $entityIdTransformerRegistry
    ) {
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    /**
     * @param string              $pointer
     * @param array               $data
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    protected function normalizeData(string $pointer, array $data, ?EntityMetadata $metadata): array
    {
        $relations = \array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)
            ? $this->normalizeRelationships($pointer, $data[JsonApiDoc::RELATIONSHIPS], $metadata)
            : [];

        return !empty($data[JsonApiDoc::ATTRIBUTES])
            ? \array_merge($data[JsonApiDoc::ATTRIBUTES], $relations)
            : $relations;
    }

    /**
     * @param string              $pointer
     * @param array               $relationships
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    protected function normalizeRelationships(string $pointer, array $relationships, ?EntityMetadata $metadata): array
    {
        $relations = [];
        $relationshipsPointer = $this->buildPointer($pointer, JsonApiDoc::RELATIONSHIPS);
        foreach ($relationships as $name => $value) {
            $relationshipsDataItemPointer = $this->buildPointer(
                $this->buildPointer($relationshipsPointer, $name),
                JsonApiDoc::DATA
            );
            $relationData = $value[JsonApiDoc::DATA];

            // Relation data can be null in case to-one and an empty array in case to-many relation.
            // In this case we should process this relation data as empty relation
            if (null === $relationData || empty($relationData)) {
                $relations[$name] = [];
                continue;
            }

            $associationMetadata = null !== $metadata
                ? $metadata->getAssociation($name)
                : null;
            if (ArrayUtil::isAssoc($relationData)) {
                $relations[$name] = $this->normalizeRelationshipItem(
                    $relationshipsDataItemPointer,
                    $relationData,
                    $associationMetadata
                );
            } else {
                foreach ($relationData as $key => $collectionItem) {
                    $relations[$name][] = $this->normalizeRelationshipItem(
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
     * @param string                   $pointer
     * @param array                    $data
     * @param AssociationMetadata|null $associationMetadata
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    protected function normalizeRelationshipItem(
        string $pointer,
        array $data,
        ?AssociationMetadata $associationMetadata
    ): array {
        $entityClass = $this->normalizeEntityClass(
            $this->buildPointer($pointer, JsonApiDoc::TYPE),
            $data[JsonApiDoc::TYPE]
        );
        $entityId = $data[JsonApiDoc::ID];
        if (false !== \strpos($entityClass, '\\')) {
            if ($this->isAcceptableTargetClass($entityClass, $associationMetadata)) {
                $targetMetadata = null;
                if (null !== $associationMetadata) {
                    $targetMetadata = $associationMetadata->getTargetMetadata();
                }
                $entityId = $this->normalizeEntityId(
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

    /**
     * @param string                   $entityClass
     * @param AssociationMetadata|null $associationMetadata
     *
     * @return bool
     */
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

    /**
     * @param string $pointer
     * @param string $entityType
     *
     * @return string
     */
    protected function normalizeEntityClass(string $pointer, string $entityType): string
    {
        $entityClass = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->context->getRequestType(),
            false
        );
        if (null === $entityClass) {
            $this->addValidationError(Constraint::ENTITY_TYPE, $pointer);
            $entityClass = $entityType;
        }

        return $entityClass;
    }

    /**
     * @param string              $pointer
     * @param string              $entityClass
     * @param mixed               $entityId
     * @param EntityMetadata|null $metadata
     *
     * @return mixed
     */
    protected function normalizeEntityId(
        string $pointer,
        string $entityClass,
        $entityId,
        ?EntityMetadata $metadata
    ) {
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
            return $this->getEntityIdTransformer($this->context->getRequestType())
                ->reverseTransform($entityId, $metadata);
        } catch (\Exception $e) {
            $this->addValidationError(Constraint::ENTITY_ID, $pointer)
                ->setInnerException($e);
        }

        return $entityId;
    }

    /**
     * @param RequestType $requestType
     *
     * @return EntityIdTransformerInterface
     */
    protected function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }

    /**
     * @param string      $title
     * @param string|null $pointer
     *
     * @return Error
     */
    protected function addValidationError(string $title, string $pointer = null): Error
    {
        $error = Error::createValidationError($title);
        if (null !== $pointer) {
            $error->setSource(ErrorSource::createByPointer($pointer));
        }
        $this->context->addError($error);

        return $error;
    }

    /**
     * @param string $parentPath
     * @param string $property
     *
     * @return string
     */
    protected function buildPointer(string $parentPath, string $property): string
    {
        return $parentPath . '/' . $property;
    }
}
