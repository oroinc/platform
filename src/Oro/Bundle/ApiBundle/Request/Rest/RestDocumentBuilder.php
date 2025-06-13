<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\LinkCollectionMetadataInterface;
use Oro\Bundle\ApiBundle\Metadata\LinkMetadataInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\DataType;
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
    public const LINKS = 'links';
    public const HREF = 'href';

    public const ERROR_CODE = 'code';
    public const ERROR_TITLE = 'title';
    public const ERROR_DETAIL = 'detail';
    public const ERROR_SOURCE = 'source';
    public const ERROR_PROPERTIES = 'properties';

    #[\Override]
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

    #[\Override]
    protected function convertCollectionToArray(
        iterable $collection,
        RequestType $requestType,
        ?EntityMetadata $metadata
    ): array {
        $result = [];
        foreach ($collection as $object) {
            $result[] = null === $object || is_scalar($object)
                ? $object
                : $this->convertObjectToArray($object, $requestType, $metadata);
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    protected function convertObjectToArray(
        mixed $object,
        RequestType $requestType,
        ?EntityMetadata $metadata
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
            $metadata = $this->getTargetMetadataProvider()->getTargetMetadata($object, $metadata);
            $hasIdentifierFields = $metadata->hasIdentifierFields();
            $objectClass = $this->objectAccessor->getClassName($object);
            if (!$objectClass) {
                $objectClass = $metadata->getClassName();
            }
            $objectAlias = $this->getEntityAlias($objectClass, $requestType);
            $objectId = $hasIdentifierFields
                ? $this->getEntityId($object, $requestType, $metadata)
                : null;

            $entityData = $data;
            $entityData[DataAccessorInterface::ENTITY_CLASS] = $objectClass;
            if ($objectAlias) {
                $entityData[DataAccessorInterface::ENTITY_TYPE] = $objectAlias;
            }
            if ($hasIdentifierFields) {
                $entityData[DataAccessorInterface::ENTITY_ID] = $objectId;
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
            $idFieldNames = $metadata->getIdentifierFieldNames();
            if (\count($idFieldNames) === 1) {
                $result[$idFieldNames[0]] = $objectId;
            }
        }

        return $result;
    }

    #[\Override]
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
        $metaProperties = $error->getMetaProperties();
        foreach ($metaProperties as $name => $metaProperty) {
            $result[self::ERROR_PROPERTIES][$name] = $metaProperty->getValue();
        }

        return $result;
    }

    #[\Override]
    protected function convertToEntityType(string $entityClass, RequestType $requestType): string
    {
        return $entityClass;
    }

    #[\Override]
    protected function tryConvertToEntityType(string $entityClass, RequestType $requestType): ?string
    {
        return $entityClass;
    }

    protected function addMeta(array &$result, array $data, EntityMetadata $metadata): void
    {
        $properties = $metadata->getMetaProperties();
        foreach ($properties as $name => $property) {
            if (!$property->isOutput()) {
                continue;
            }
            $propertyPath = $property->getPropertyPath();
            if (!$propertyPath || $this->isIgnoredMeta($propertyPath, $metadata)) {
                continue;
            }
            $resultName = $property->getResultName();
            if (\array_key_exists($name, $data)) {
                $result[$resultName] = $data[$name];
            } else {
                $value = null;
                if ($this->resultDataAccessor->tryGetValue($propertyPath, $value)) {
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

    #[\Override]
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

    #[\Override]
    protected function addMetaToCollectionResult(array &$result, string $name, mixed $value): void
    {
        // not supported
    }

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

            if (DataType::isAssociationAsField($association->getDataType())) {
                $result[$name] = $this->getRelationshipValue(
                    $data,
                    $requestType,
                    $name,
                    $association
                );
                continue;
            }

            $this->resultDataAccessor->setAssociation(
                $name,
                $this->getRelationshipData($requestType, $association)
            );

            $result[$name] = $this->getRelationshipValue($data, $requestType, $name, $association);
        }
    }

    #[\Override]
    protected function processRelatedObject(
        mixed $object,
        RequestType $requestType,
        AssociationMetadata $associationMetadata
    ): mixed {
        if (is_scalar($object)) {
            return $this->getEntityId($object, $requestType, $associationMetadata->getTargetMetadata());
        }

        $this->resultDataAccessor->addEntity();
        try {
            $targetMetadata = $this->getTargetMetadataProvider()
                ->getAssociationTargetMetadata($object, $associationMetadata);
            if ($targetMetadata && $this->hasIdentifierFieldsOnly($targetMetadata)) {
                $data = $this->objectAccessor->toArray($object);

                return \count($data) === 1
                    ? reset($data)
                    : $data;
            }

            return $this->convertObjectToArray($object, $requestType, $targetMetadata);
        } finally {
            $this->resultDataAccessor->removeLastEntity();
        }
    }

    #[\Override]
    protected function addRelatedObject(array $object): void
    {
        throw new \LogicException('The included objects are not supported by this document.');
    }

    #[\Override]
    protected function hasIdentifierFieldsOnly(EntityMetadata $metadata): bool
    {
        if (\count($metadata->getMetaProperties()) > 0) {
            return false;
        }

        return parent::hasIdentifierFieldsOnly($metadata);
    }

    protected function getLinkObject(string $href, ?array $properties): array|string
    {
        if (empty($properties)) {
            return $href;
        }

        return array_merge([self::HREF => $href], $properties);
    }
}
