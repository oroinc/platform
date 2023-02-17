<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Exception\LinkHrefResolvingFailedException;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\LinkMetadataInterface;
use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use Oro\Bundle\ApiBundle\Metadata\TargetMetadataProvider;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\AssociationToArrayAttributeConverter;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\EntityIdAccessor;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ObjectAccessor;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ObjectAccessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Psr\Log\LoggerInterface;

/**
 * The base class for document builders for different types of API responses.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractDocumentBuilder implements DocumentBuilderInterface
{
    public const DATA = 'data';
    public const ERRORS = 'errors';

    public const LINK_SELF = 'self';
    public const LINK_RELATED = 'related';
    public const LINK_FIRST = 'first';
    public const LINK_LAST = 'last';
    public const LINK_PREV = 'prev';
    public const LINK_NEXT = 'next';

    protected ValueNormalizer $valueNormalizer;
    protected LoggerInterface $logger;
    protected ObjectAccessorInterface $objectAccessor;
    protected DocumentBuilderDataAccessor $resultDataAccessor;
    protected array $result = [];
    /** @var array [name => [href, meta properties] or LinkMetadataInterface, ...] */
    protected array $links = [];
    private EntityIdTransformerRegistry $entityIdTransformerRegistry;
    private EntityIdAccessor $entityIdAccessor;
    private ?AssociationToArrayAttributeConverter $arrayAttributeConverter = null;
    private ?TargetMetadataProvider $targetMetadataProvider = null;

    public function __construct(
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerRegistry $entityIdTransformerRegistry,
        LoggerInterface $logger
    ) {
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
        $this->logger = $logger;

        $this->objectAccessor = new ObjectAccessor();
        $this->entityIdAccessor = new EntityIdAccessor(
            $this->objectAccessor,
            $this->entityIdTransformerRegistry
        );
        $this->resultDataAccessor = new DocumentBuilderDataAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocument(): array
    {
        return $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->resultDataAccessor->clear();
        $this->result = [];
        $this->links = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAlias(string $entityClass, RequestType $requestType): ?string
    {
        return ValueNormalizerUtil::tryConvertToEntityType($this->valueNormalizer, $entityClass, $requestType);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityId(mixed $entity, RequestType $requestType, EntityMetadata $metadata): string
    {
        return $this->entityIdAccessor->getEntityId($entity, $metadata, $requestType);
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata(array $metadata): void
    {
        $this->resultDataAccessor->setMetadata($metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function setDataObject(mixed $object, RequestType $requestType, ?EntityMetadata $metadata): void
    {
        $this->assertNoData();

        $this->result[self::DATA] = null;
        if (null !== $object) {
            $this->resultDataAccessor->setCollection(false);
            $this->resultDataAccessor->addEntity();
            try {
                $this->result[self::DATA] = $this->convertObjectToArray($object, $requestType, $metadata);
            } finally {
                $this->resultDataAccessor->clear();
            }
        }
        $this->addLinksWithMetadataToResult($this->result);
    }

    /**
     * {@inheritdoc}
     */
    public function setDataCollection($collection, RequestType $requestType, ?EntityMetadata $metadata): void
    {
        $this->assertNoData();

        $this->result[self::DATA] = [];
        if (\is_iterable($collection)) {
            $this->resultDataAccessor->setCollection(true);
            $this->resultDataAccessor->addEntity();
            try {
                $this->result[self::DATA] = $this->convertCollectionToArray($collection, $requestType, $metadata);
            } finally {
                $this->resultDataAccessor->clear();
            }
            $this->addLinksWithMetadataToResult($this->result);
            $this->addCollectionMetadataToResult($this->result);
        } else {
            throw $this->createUnexpectedValueException('array or \Traversable', $collection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addIncludedObject(mixed $object, RequestType $requestType, ?EntityMetadata $metadata): void
    {
        $this->assertData();

        if (null !== $object) {
            $this->resultDataAccessor->addEntity();
            try {
                $this->addRelatedObject($this->convertObjectToArray($object, $requestType, $metadata));
            } finally {
                $this->resultDataAccessor->clear();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addLink(string $name, string $href, array $properties = []): void
    {
        $this->assertData();
        $this->links[$name] = [$href, $properties];
    }

    /**
     * {@inheritdoc}
     */
    public function addLinkMetadata(string $name, LinkMetadataInterface $link): void
    {
        $this->assertNoData();
        $this->links[$name] = $link;
    }

    /**
     * {@inheritdoc}
     */
    public function setErrorObject(Error $error): void
    {
        $this->assertNoData();

        $this->result[self::ERRORS] = [$this->convertErrorToArray($error)];
    }

    /**
     * {@inheritdoc}
     */
    public function setErrorCollection(array $errors): void
    {
        $this->assertNoData();

        $errorsData = [];
        foreach ($errors as $error) {
            $errorsData[] = $this->convertErrorToArray($error);
        }
        $this->result[self::ERRORS] = $errorsData;
    }

    protected function convertCollectionToArray(
        iterable $collection,
        RequestType $requestType,
        ?EntityMetadata $metadata
    ): array {
        $result = [];
        foreach ($collection as $object) {
            $result[] = $this->convertObjectToArray($object, $requestType, $metadata);
        }

        return $result;
    }

    abstract protected function convertObjectToArray(
        mixed $object,
        RequestType $requestType,
        ?EntityMetadata $metadata
    ): array;

    abstract protected function convertErrorToArray(Error $error): array;

    abstract protected function convertToEntityType(string $entityClass, RequestType $requestType): string;

    abstract protected function tryConvertToEntityType(string $entityClass, RequestType $requestType): ?string;

    protected function getEntityTypeForObject(
        mixed $object,
        RequestType $requestType,
        EntityMetadata $metadata
    ): string {
        $className = $this->objectAccessor->getClassName($object);

        return $className
            ? $this->getEntityType($className, $requestType, $metadata->getClassName())
            : $this->getEntityType($metadata->getClassName(), $requestType);
    }

    protected function getEntityType(
        ?string $entityClass,
        RequestType $requestType,
        string $fallbackEntityClass = null
    ): string {
        $entityType = null;
        if ($entityClass) {
            $entityType = $this->tryConvertToEntityType($entityClass, $requestType);
        }
        if (!$entityType && $fallbackEntityClass) {
            $entityType = $this->convertToEntityType($fallbackEntityClass, $requestType);
        }

        return $entityType;
    }

    protected function getLinkHref(LinkMetadataInterface $linkMetadata): ?string
    {
        try {
            return $linkMetadata->getHref($this->resultDataAccessor);
        } catch (LinkHrefResolvingFailedException $e) {
            $this->logger->notice($e->getMessage(), ['exception' => $e]);
        }

        return null;
    }

    protected function getLinkMeta(LinkMetadataInterface $linkMetadata): ?array
    {
        $properties = $linkMetadata->getMetaProperties();
        if (empty($properties)) {
            return null;
        }

        return $this->resolveMeta($properties);
    }

    protected function getAssociationMeta(AssociationMetadata $associationMetadata): ?array
    {
        $properties = $associationMetadata->getMetaProperties();
        if (empty($properties)) {
            return null;
        }

        return $this->resolveMeta($properties);
    }

    protected function getRelationshipMeta(AssociationMetadata $associationMetadata): ?array
    {
        $properties = $associationMetadata->getRelationshipMetaProperties();
        if (empty($properties)) {
            return null;
        }

        return $this->resolveMeta($properties);
    }

    /**
     * @param MetaAttributeMetadata[] $properties
     *
     * @return array|null
     */
    protected function resolveMeta(array $properties): ?array
    {
        $result = [];
        foreach ($properties as $name => $property) {
            $value = null;
            if ($this->resultDataAccessor->tryGetValue($property->getPropertyPath(), $value)) {
                $result[$name] = $value;
            }
        }

        return !empty($result) ? $result : null;
    }

    protected function isIgnoredMeta(string $propertyPath, EntityMetadata $metadata): bool
    {
        return
            ConfigUtil::CLASS_NAME === $propertyPath
            && (
                $metadata->isInheritedType()
                || is_a($metadata->getClassName(), EntityIdentifier::class, true)
            );
    }

    protected function getCollectionAssociationData(array $data, string $associationName): ?array
    {
        if (!\array_key_exists($associationName, $data)) {
            return null;
        }
        $items = $data[$associationName];
        if (!\is_array($items) || empty($items)) {
            return null;
        }

        return $items;
    }

    /**
     * Checks whether a given metadata contains only identifier fields(s).
     */
    protected function hasIdentifierFieldsOnly(EntityMetadata $metadata): bool
    {
        return $metadata->hasIdentifierFieldsOnly();
    }

    protected function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }

    protected function getArrayAttributeConverter(): AssociationToArrayAttributeConverter
    {
        if (null === $this->arrayAttributeConverter) {
            $this->arrayAttributeConverter = $this->createArrayAttributeConverter();
        }

        return $this->arrayAttributeConverter;
    }

    protected function createArrayAttributeConverter(): AssociationToArrayAttributeConverter
    {
        return new AssociationToArrayAttributeConverter(
            $this->objectAccessor,
            $this->getTargetMetadataProvider()
        );
    }

    protected function getTargetMetadataProvider(): TargetMetadataProvider
    {
        if (null === $this->targetMetadataProvider) {
            $this->targetMetadataProvider = $this->createTargetMetadataProvider();
        }

        return $this->targetMetadataProvider;
    }

    protected function createTargetMetadataProvider(): TargetMetadataProvider
    {
        return new TargetMetadataProvider($this->objectAccessor);
    }

    protected function isEmptyRelationship(mixed $value, bool $isCollection): bool
    {
        return $isCollection
            ? empty($value)
            : null === $value;
    }

    protected function getRelationshipValue(
        array $data,
        RequestType $requestType,
        string $associationName,
        AssociationMetadata $associationMetadata
    ): mixed {
        $result = null;
        $isCollection = $associationMetadata->isCollection();
        if (\array_key_exists($associationName, $data)) {
            $val = $data[$associationName];
            if (!$this->isEmptyRelationship($val, $isCollection)) {
                if (DataType::isAssociationAsField($associationMetadata->getDataType())) {
                    $result = $isCollection
                        ? $this->getArrayAttributeConverter()->convertCollectionToArray($val, $associationMetadata)
                        : $this->getArrayAttributeConverter()->convertObjectToArray($val, $associationMetadata);
                } else {
                    $result = $isCollection
                        ? $this->processRelatedCollection($val, $requestType, $associationMetadata)
                        : $this->processRelatedObject($val, $requestType, $associationMetadata);
                }
            }
        }
        if (null === $result && $isCollection) {
            $result = [];
        }

        return $result;
    }

    protected function getRelationshipData(RequestType $requestType, AssociationMetadata $associationMetadata): array
    {
        $targetClass = $associationMetadata->getTargetClassName();
        $targetAlias = $this->getEntityAlias($targetClass, $requestType);

        $data = [DataAccessorInterface::ENTITY_CLASS => $targetClass];
        if ($targetAlias) {
            $data[DataAccessorInterface::ENTITY_TYPE] = $targetAlias;
        }

        return $data;
    }

    protected function processRelatedCollection(
        iterable $collection,
        RequestType $requestType,
        AssociationMetadata $associationMetadata
    ): array {
        $result = [];
        $i = 0;
        foreach ($collection as $object) {
            $this->resultDataAccessor->setAssociationIndex($i);
            $result[] = $this->processRelatedObject($object, $requestType, $associationMetadata);
            $i++;
        }

        return $result;
    }

    abstract protected function processRelatedObject(
        mixed $object,
        RequestType $requestType,
        AssociationMetadata $associationMetadata
    ): mixed;

    abstract protected function addRelatedObject(array $object): void;

    abstract protected function addLinkToResult(array &$result, string $name, LinkMetadataInterface $link): void;

    abstract protected function addMetaToCollectionResult(array &$result, string $name, mixed $value): void;

    /**
     * Checks that the primary data exists.
     */
    protected function assertData(): void
    {
        if (!\array_key_exists(self::DATA, $this->result)) {
            throw new \InvalidArgumentException('A primary data should be set.');
        }
    }

    /**
     * Checks that the primary data does not exist.
     */
    protected function assertNoData(): void
    {
        if (\array_key_exists(self::DATA, $this->result)) {
            throw new \InvalidArgumentException('A primary data already exist.');
        }
    }

    protected function createUnexpectedValueException(string $expectedType, mixed $value): \UnexpectedValueException
    {
        return new \UnexpectedValueException(sprintf(
            'Expected argument of type "%s", "%s" given.',
            $expectedType,
            get_debug_type($value)
        ));
    }

    private function addLinksWithMetadataToResult(array &$result): void
    {
        if (empty($this->links)) {
            return;
        }

        $links = [];
        foreach ($this->links as $name => $link) {
            if ($link instanceof LinkMetadataInterface) {
                $links[$name] = $link;
            }
        }
        if (empty($links)) {
            return;
        }

        $this->resultDataAccessor->setCollection(false);
        $this->resultDataAccessor->addEntity();
        try {
            foreach ($links as $name => $link) {
                $this->addLinkToResult($result, $name, $link);
            }
        } finally {
            $this->resultDataAccessor->clear();
        }
    }

    private function addCollectionMetadataToResult(array &$result): void
    {
        $data = $this->resultDataAccessor->getMetadata();
        if (empty($data)) {
            return;
        }

        foreach ($data as $name => $value) {
            if ('' !== $name && !str_contains($name, '.')) {
                $this->addMetaToCollectionResult($result, $name, $value);
            }
        }
    }
}
