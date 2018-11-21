<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Exception\LinkHrefResolvingFailedException;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\LinkMetadataInterface;
use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\AssociationToArrayAttributeConverter;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\EntityIdAccessor;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ObjectAccessor;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ObjectAccessorInterface;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Psr\Log\LoggerInterface;

/**
 * The base class for document builders for different types of Data API responses.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractDocumentBuilder implements DocumentBuilderInterface
{
    public const DATA   = 'data';
    public const ERRORS = 'errors';

    public const LINK_SELF    = 'self';
    public const LINK_RELATED = 'related';
    public const LINK_FIRST   = 'first';
    public const LINK_LAST    = 'last';
    public const LINK_PREV    = 'prev';
    public const LINK_NEXT    = 'next';

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ObjectAccessorInterface */
    protected $objectAccessor;

    /** @var DocumentBuilderDataAccessor */
    protected $resultDataAccessor;

    /** @var array */
    protected $result = [];

    /** @var array [name => [href, meta properties] or LinkMetadataInterface, ...] */
    protected $links = [];

    /** @var EntityIdTransformerRegistry */
    private $entityIdTransformerRegistry;

    /** @var EntityIdAccessor */
    private $entityIdAccessor;

    /** @var AssociationToArrayAttributeConverter */
    private $arrayAttributeConverter;

    /**
     * @param ValueNormalizer             $valueNormalizer
     * @param EntityIdTransformerRegistry $entityIdTransformerRegistry
     * @param LoggerInterface             $logger
     */
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
        return ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $entityClass, $requestType, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityId($entity, RequestType $requestType, EntityMetadata $metadata): string
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
    public function setDataObject($object, RequestType $requestType, EntityMetadata $metadata = null): void
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
    public function setDataCollection($collection, RequestType $requestType, EntityMetadata $metadata = null): void
    {
        $this->assertNoData();

        $this->result[self::DATA] = [];
        if (\is_array($collection) || $collection instanceof \Traversable) {
            $this->resultDataAccessor->setCollection(true);
            $this->resultDataAccessor->addEntity();
            try {
                $this->result[self::DATA] = $this->convertCollectionToArray($collection, $requestType, $metadata);
            } finally {
                $this->resultDataAccessor->clear();
            }
            $this->addLinksWithMetadataToResult($this->result);
        } else {
            throw $this->createUnexpectedValueException('array or \Traversable', $collection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addIncludedObject($object, RequestType $requestType, EntityMetadata $metadata = null): void
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

    /**
     * @param array|\Traversable  $collection
     * @param RequestType         $requestType
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    protected function convertCollectionToArray(
        $collection,
        RequestType $requestType,
        EntityMetadata $metadata = null
    ): array {
        $result = [];
        foreach ($collection as $object) {
            $result[] = $this->convertObjectToArray($object, $requestType, $metadata);
        }

        return $result;
    }

    /**
     * @param mixed               $object
     * @param RequestType         $requestType
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    abstract protected function convertObjectToArray(
        $object,
        RequestType $requestType,
        EntityMetadata $metadata = null
    ): array;

    /**
     * @param Error $error
     *
     * @return array
     */
    abstract protected function convertErrorToArray(Error $error): array;

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     * @param bool        $throwException
     *
     * @return string|null
     */
    abstract protected function convertToEntityType(
        string $entityClass,
        RequestType $requestType,
        bool $throwException = true
    ): ?string;

    /**
     * @param mixed          $object
     * @param RequestType    $requestType
     * @param EntityMetadata $metadata
     *
     * @return string
     */
    protected function getEntityTypeForObject($object, RequestType $requestType, EntityMetadata $metadata): string
    {
        $className = $this->objectAccessor->getClassName($object);

        return $className
            ? $this->getEntityType($className, $requestType, $metadata->getClassName())
            : $this->getEntityType($metadata->getClassName(), $requestType);
    }

    /**
     * @param string|null $entityClass
     * @param RequestType $requestType
     * @param string|null $fallbackEntityClass
     *
     * @return string
     */
    protected function getEntityType(
        ?string $entityClass,
        RequestType $requestType,
        string $fallbackEntityClass = null
    ): string {
        $entityType = null;
        if ($entityClass) {
            $entityType = $this->convertToEntityType($entityClass, $requestType, false);
        }
        if (!$entityType && $fallbackEntityClass) {
            $entityType = $this->convertToEntityType($fallbackEntityClass, $requestType);
        }

        return $entityType;
    }

    /**
     * @param LinkMetadataInterface $linkMetadata
     *
     * @return string|null
     */
    protected function getLinkHref(LinkMetadataInterface $linkMetadata): ?string
    {
        try {
            return $linkMetadata->getHref($this->resultDataAccessor);
        } catch (LinkHrefResolvingFailedException $e) {
            $this->logger->notice($e->getMessage(), ['exception' => $e]);
        }

        return null;
    }

    /**
     * @param LinkMetadataInterface $linkMetadata
     *
     * @return array|null
     */
    protected function getLinkMeta(LinkMetadataInterface $linkMetadata): ?array
    {
        $properties = $linkMetadata->getMetaProperties();
        if (empty($properties)) {
            return null;
        }

        return $this->resolveMeta($properties);
    }

    /**
     * @param AssociationMetadata $associationMetadata
     *
     * @return array|null
     */
    protected function getAssociationMeta(AssociationMetadata $associationMetadata): ?array
    {
        $properties = $associationMetadata->getMetaProperties();
        if (empty($properties)) {
            return null;
        }

        return $this->resolveMeta($properties);
    }

    /**
     * @param AssociationMetadata $associationMetadata
     *
     * @return array|null
     */
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

    /**
     * @param array  $data
     * @param string $associationName
     *
     * @return array|null
     */
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
     *
     * @param EntityMetadata $metadata
     *
     * @return bool
     */
    protected function hasIdentifierFieldsOnly(EntityMetadata $metadata): bool
    {
        return $metadata->hasIdentifierFieldsOnly();
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
     * @return AssociationToArrayAttributeConverter
     */
    protected function getArrayAttributeConverter(): AssociationToArrayAttributeConverter
    {
        if (null === $this->arrayAttributeConverter) {
            $this->arrayAttributeConverter = $this->createArrayAttributeConverter();
        }

        return $this->arrayAttributeConverter;
    }

    /**
     * @return AssociationToArrayAttributeConverter
     */
    protected function createArrayAttributeConverter(): AssociationToArrayAttributeConverter
    {
        return new AssociationToArrayAttributeConverter($this->objectAccessor);
    }

    /**
     * @param mixed $value
     * @param bool  $isCollection
     *
     * @return bool
     */
    protected function isEmptyRelationship($value, bool $isCollection): bool
    {
        return $isCollection
            ? empty($value)
            : null === $value;
    }

    /**
     * @param array               $data
     * @param RequestType         $requestType
     * @param string              $associationName
     * @param AssociationMetadata $associationMetadata
     *
     * @return mixed
     */
    protected function getRelationshipValue(
        array $data,
        RequestType $requestType,
        string $associationName,
        AssociationMetadata $associationMetadata
    ) {
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

    /**
     * @param RequestType         $requestType
     * @param AssociationMetadata $association
     *
     * @return array
     */
    protected function getRelationshipData(RequestType $requestType, AssociationMetadata $association): array
    {
        $targetClass = $association->getTargetClassName();
        $targetAlias = $this->getEntityAlias($targetClass, $requestType);

        $data = [DataAccessorInterface::ENTITY_CLASS => $targetClass];
        if ($targetAlias) {
            $data[DataAccessorInterface::ENTITY_TYPE] = $targetAlias;
        }

        return $data;
    }

    /**
     * @param array|\Traversable  $collection
     * @param RequestType         $requestType
     * @param AssociationMetadata $associationMetadata
     *
     * @return array
     */
    protected function processRelatedCollection(
        $collection,
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

    /**
     * @param mixed               $object
     * @param RequestType         $requestType
     * @param AssociationMetadata $associationMetadata
     *
     * @return mixed
     */
    abstract protected function processRelatedObject(
        $object,
        RequestType $requestType,
        AssociationMetadata $associationMetadata
    );

    /**
     * @param array $object
     */
    abstract protected function addRelatedObject(array $object): void;

    /**
     * @param array                 $result
     * @param string                $name
     * @param LinkMetadataInterface $link
     */
    abstract protected function addLinkToResult(array &$result, string $name, LinkMetadataInterface $link): void;

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

    /**
     * @param string $expectedType
     * @param mixed  $value
     *
     * @return \UnexpectedValueException
     */
    protected function createUnexpectedValueException(string $expectedType, $value): \UnexpectedValueException
    {
        return new \UnexpectedValueException(\sprintf(
            'Expected argument of type "%s", "%s" given.',
            $expectedType,
            \is_object($value) ? \get_class($value) : \gettype($value)
        ));
    }

    /**
     * @param array $result
     */
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
}
