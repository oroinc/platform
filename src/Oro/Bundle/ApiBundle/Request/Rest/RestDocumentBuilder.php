<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\LinkCollectionMetadataInterface;
use Oro\Bundle\ApiBundle\Metadata\LinkMetadataInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The document builder for plain REST API response.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RestDocumentBuilder extends AbstractDocumentBuilder
{
    public const OBJECT_TYPE = 'entity';
    public const LINKS       = 'links';
    public const HREF        = 'href';

    private const ERROR_CODE   = 'code';
    private const ERROR_TITLE  = 'title';
    private const ERROR_DETAIL = 'detail';
    private const ERROR_SOURCE = 'source';

    /**
     * {@inheritdoc}
     */
    public function getDocument(): array
    {
        $result = null;
        if (\array_key_exists(self::DATA, $this->result)) {
            $result = $this->result[self::DATA];
        } elseif (\array_key_exists(self::ERRORS, $this->result)) {
            $result = $this->result[self::ERRORS];
        }
        if (null === $result) {
            $result = [];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertCollectionToArray(
        $collection,
        RequestType $requestType,
        EntityMetadata $metadata = null
    ): array {
        $result = [];
        foreach ($collection as $object) {
            $result[] = null === $object || \is_scalar($object)
                ? $object
                : $this->convertObjectToArray($object, $requestType, $metadata);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertObjectToArray(
        $object,
        RequestType $requestType,
        EntityMetadata $metadata = null
    ): array {
        $data = $this->objectAccessor->toArray($object);
        if (null === $metadata) {
            if (!\array_key_exists(self::OBJECT_TYPE, $data)) {
                $objectClass = $this->objectAccessor->getClassName($object);
                if ($objectClass) {
                    $data[self::OBJECT_TYPE] = $objectClass;
                }
            }
            $result = $data;
        } else {
            $objectClass = $this->objectAccessor->getClassName($object);
            if (!$objectClass) {
                $objectClass = $metadata->getClassName();
            }
            $objectAlias = $this->getEntityAlias($objectClass, $requestType);

            $entityData = $data;
            $entityData[DataAccessorInterface::ENTITY_CLASS] = $objectClass;
            if ($objectAlias) {
                $entityData[DataAccessorInterface::ENTITY_TYPE] = $objectAlias;
            }
            if ($metadata->hasIdentifierFields()) {
                $entityData[DataAccessorInterface::ENTITY_ID] = $this->getEntityId($object, $requestType, $metadata);
            }
            $this->resultDataAccessor->setEntity($entityData);

            $result = [];
            if ($metadata->hasMetaProperty(ConfigUtil::CLASS_NAME)) {
                $result[self::OBJECT_TYPE] = $this->getEntityTypeForObject($object, $requestType, $metadata);
            }
            $this->addMeta($result, $data, $metadata);
            $this->addLinks($result, $metadata->getLinks());
            $this->addAttributes($result, $data, $metadata);
            $this->addRelationships($result, $data, $requestType, $metadata);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertErrorToArray(Error $error): array
    {
        $result = [];

        if ($error->getCode()) {
            $result[self::ERROR_CODE] = (string)$error->getCode();
        }
        if ($error->getTitle()) {
            $result[self::ERROR_TITLE] = $error->getTitle();
        }
        if ($error->getDetail()) {
            $result[self::ERROR_DETAIL] = $error->getDetail();
        }
        $source = $error->getSource();
        if ($source) {
            if ($source->getPointer()) {
                $result[self::ERROR_SOURCE] = $source->getPointer();
            } elseif ($source->getParameter()) {
                $result[self::ERROR_SOURCE] = $source->getParameter();
            } elseif ($source->getPropertyPath()) {
                $result[self::ERROR_SOURCE] = $source->getPropertyPath();
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertToEntityType(
        string $entityClass,
        RequestType $requestType,
        bool $throwException = true
    ): ?string {
        return $entityClass;
    }

    /**
     * @param array          $result
     * @param array          $data
     * @param EntityMetadata $metadata
     */
    protected function addMeta(array &$result, array $data, EntityMetadata $metadata): void
    {
        $properties = $metadata->getMetaProperties();
        foreach ($properties as $name => $property) {
            if (!$property->isOutput()) {
                continue;
            }
            $resultName = $property->getResultName();
            if (\array_key_exists($name, $data)) {
                $result[$resultName] = $data[$name];
            } else {
                $value = null;
                if ($this->resultDataAccessor->tryGetValue($property->getPropertyPath(), $value)) {
                    $result[$resultName] = $value;
                }
            }
        }
    }

    /**
     * @param array                   $result
     * @param LinkMetadataInterface[] $links
     */
    protected function addLinks(array &$result, array $links): void
    {
        foreach ($links as $name => $link) {
            $this->addLinkToResult($result, $name, $link);
        }
    }

    /**
     * @param array                 $result
     * @param string                $name
     * @param LinkMetadataInterface $link
     */
    protected function addLinkToResult(array &$result, string $name, LinkMetadataInterface $link): void
    {
        $href = $this->getLinkHref($link);
        if ($href) {
            $result[self::LINKS][$name] = $this->getLinkObject($href, $this->getLinkMeta($link));
        }
        if ($link instanceof LinkCollectionMetadataInterface) {
            $this->addLinks($result, $link->getLinks($this->resultDataAccessor));
        }
    }

    /**
     * @param array          $result
     * @param array          $data
     * @param EntityMetadata $metadata
     */
    protected function addAttributes(array &$result, array $data, EntityMetadata $metadata): void
    {
        $fields = $metadata->getFields();
        foreach ($fields as $name => $field) {
            if (!$field->isOutput()) {
                continue;
            }
            $result[$name] = $data[$name] ?? null;
        }
    }

    /**
     * @param array          $result
     * @param array          $data
     * @param RequestType    $requestType
     * @param EntityMetadata $metadata
     */
    protected function addRelationships(
        array &$result,
        array $data,
        RequestType $requestType,
        EntityMetadata $metadata
    ): void {
        $associations = $metadata->getAssociations();
        foreach ($associations as $name => $association) {
            if (!$association->isOutput()) {
                continue;
            }

            $this->resultDataAccessor->setAssociation(
                $name,
                $this->getRelationshipData($requestType, $association)
            );

            $result[$name] = $this->getRelationshipValue($data, $requestType, $name, $association);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function processRelatedObject(
        $object,
        RequestType $requestType,
        AssociationMetadata $associationMetadata
    ) {
        if (\is_scalar($object)) {
            return $object;
        }

        $this->resultDataAccessor->addEntity();
        try {
            $targetMetadata = $associationMetadata->getTargetMetadata();
            if ($targetMetadata && $this->hasIdentifierFieldsOnly($targetMetadata)) {
                $data = $this->objectAccessor->toArray($object);

                return count($data) === 1
                    ? reset($data)
                    : $data;
            }

            return $this->convertObjectToArray($object, $requestType, $targetMetadata);
        } finally {
            $this->resultDataAccessor->removeLastEntity();
        }
    }

    /**
     * @param array $object
     */
    protected function addRelatedObject(array $object): void
    {
        throw new \LogicException('The included objects are not supported by this document.');
    }

    /**
     * {@inheritdoc}
     */
    protected function hasIdentifierFieldsOnly(EntityMetadata $metadata): bool
    {
        if (count($metadata->getMetaProperties()) > 0) {
            return false;
        }

        return parent::hasIdentifierFieldsOnly($metadata);
    }

    /**
     * @param string     $href
     * @param array|null $properties
     *
     * @return array|string
     */
    protected function getLinkObject(string $href, ?array $properties)
    {
        if (empty($properties)) {
            return $href;
        }

        return \array_merge([self::HREF => $href], $properties);
    }
}
