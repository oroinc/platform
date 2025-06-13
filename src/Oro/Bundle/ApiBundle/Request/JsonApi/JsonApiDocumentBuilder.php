<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\LinkCollectionMetadataInterface;
use Oro\Bundle\ApiBundle\Metadata\LinkMetadataInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * The document builder for REST API response conforms JSON:API specification.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class JsonApiDocumentBuilder extends AbstractDocumentBuilder
{
    public const JSONAPI = 'jsonapi';
    public const LINKS = 'links';
    public const META = 'meta';
    public const INCLUDED = 'included';
    public const ATTRIBUTES = 'attributes';
    public const RELATIONSHIPS = 'relationships';
    public const ID = 'id';
    public const TYPE = 'type';
    public const HREF = 'href';
    public const META_UPDATE = 'update';
    public const META_UPSERT = 'upsert';
    public const META_VALIDATE = 'validate';

    public const ERROR_ID = 'id';
    public const ERROR_STATUS = 'status';
    public const ERROR_CODE = 'code';
    public const ERROR_TITLE = 'title';
    public const ERROR_DETAIL = 'detail';
    public const ERROR_SOURCE = 'source';
    public const ERROR_POINTER = 'pointer';
    public const ERROR_PARAMETER = 'parameter';

    #[\Override]
    public function getDocument(): array
    {
        $result = $this->result;
        // check whether the result document data contains only a meta information
        if (\array_key_exists(self::DATA, $result)) {
            $data = $result[self::DATA];
            if (\is_array($data) && \array_key_exists(self::META, $data) && \count($data) === 1) {
                $result[self::META] = $data[self::META];
                unset($result[self::DATA]);
            }
            foreach ($this->links as $name => $link) {
                if (\is_array($link)) {
                    $result[self::LINKS][$name] = $this->getLinkObject($link[0], $link[1]);
                }
            }
        }

        return $result;
    }

    #[\Override]
    public function setDataObject(mixed $object, RequestType $requestType, ?EntityMetadata $metadata): void
    {
        parent::setDataObject($object, $requestType, $metadata);
        $this->removeRelatedObjectsThatDuplicatePrimaryObjects(false);
    }

    #[\Override]
    public function setDataCollection($collection, RequestType $requestType, ?EntityMetadata $metadata): void
    {
        parent::setDataCollection($collection, $requestType, $metadata);
        $this->removeRelatedObjectsThatDuplicatePrimaryObjects(true);
    }

    #[\Override]
    protected function convertCollectionToArray(
        iterable $collection,
        RequestType $requestType,
        ?EntityMetadata $metadata
    ): array {
        if (null !== $metadata) {
            return parent::convertCollectionToArray($collection, $requestType, $metadata);
        }

        $items = [];
        foreach ($collection as $object) {
            $item = $this->convertObjectToArray($object, $requestType, null);
            $items[] = $item[self::META];
        }

        return [self::META => [self::DATA => $items]];
    }

    #[\Override]
    protected function convertObjectToArray(
        mixed $object,
        RequestType $requestType,
        ?EntityMetadata $metadata
    ): array {
        $data = $this->objectAccessor->toArray($object);
        if (null === $metadata) {
            $result = [self::META => $data];
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

            if ($hasIdentifierFields) {
                $result = $this->getResourceIdObject(
                    $this->getEntityTypeForObject($object, $requestType, $metadata),
                    $objectId
                );
                $this->addMeta($result, $data, $metadata);
                $this->addLinks($result, $metadata->getLinks());
                $this->addAttributes($result, $data, $metadata);
                $this->addRelationships($result, $data, $requestType, $metadata);
            } else {
                $result = [];
                $this->addMeta($result, $data, $metadata);
                $this->addLinks($result, $metadata->getLinks());
                $this->addAttributesAsMeta($result, $data, $metadata);
                $this->addRelationshipsAsMeta($result, $data, $requestType, $metadata);
            }
        }

        return $result;
    }

    #[\Override]
    protected function convertErrorToArray(Error $error): array
    {
        $result = [];

        if ($error->getStatusCode()) {
            $result[self::ERROR_STATUS] = (string)$error->getStatusCode();
        }
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
                $result[self::ERROR_SOURCE][self::ERROR_POINTER] = $source->getPointer();
            } elseif ($source->getParameter()) {
                $result[self::ERROR_SOURCE][self::ERROR_PARAMETER] = $source->getParameter();
            }
        }
        $metaProperties = $error->getMetaProperties();
        foreach ($metaProperties as $name => $metaProperty) {
            $result[self::META][$name] = $metaProperty->getValue();
        }

        return $result;
    }

    #[\Override]
    protected function convertToEntityType(string $entityClass, RequestType $requestType): string
    {
        return ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $entityClass, $requestType);
    }

    #[\Override]
    protected function tryConvertToEntityType(string $entityClass, RequestType $requestType): ?string
    {
        return ValueNormalizerUtil::tryConvertToEntityType($this->valueNormalizer, $entityClass, $requestType);
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
                $result[self::META][$resultName] = $data[$name];
            } else {
                $value = null;
                if ($this->resultDataAccessor->tryGetValue($propertyPath, $value)) {
                    $result[self::META][$resultName] = $value;
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
        $result[self::META][$name] = $value;
    }

    protected function addAttributes(array &$result, array $data, EntityMetadata $metadata): void
    {
        $idFieldNames = $metadata->getIdentifierFieldNames();
        $fields = $metadata->getFields();
        foreach ($fields as $name => $field) {
            if (!$field->isOutput()) {
                continue;
            }
            if (!\in_array($name, $idFieldNames, true)) {
                $result[self::ATTRIBUTES][$name] = $data[$name] ?? null;
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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

            if (DataType::isAssociationAsField($association->getDataType())) {
                $result[self::ATTRIBUTES][$name] = $this->getRelationshipValue(
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

            $relationshipValue = [];
            $this->addAssociationMeta($relationshipValue, $this->getRelationshipMeta($association));
            $this->addLinks($relationshipValue, $association->getRelationshipLinks());

            $value = $this->getRelationshipValue($data, $requestType, $name, $association);
            if ($association->isCollection()) {
                $items = $this->getCollectionAssociationData($data, $name);
                if (!empty($items)) {
                    $i = 0;
                    foreach ($items as $item) {
                        $this->resultDataAccessor->setAssociation(
                            $name,
                            $this->getAssociationData($requestType, $item, $value[$i][self::ID]),
                            $i
                        );
                        $this->addAssociationMeta($value[$i], $this->getAssociationMeta($association));
                        if (\is_array($item)) {
                            $this->addMeta($value[$i], $item, $association->getTargetMetadata());
                        }
                        $this->addLinks($value[$i], $association->getLinks());
                        $i++;
                    }
                }
            } elseif (null !== $value) {
                $item = $data[$name] ?? null;
                $this->resultDataAccessor->setAssociation(
                    $name,
                    $this->getAssociationData($requestType, $item, $value[self::ID])
                );
                $this->addAssociationMeta($value, $this->getAssociationMeta($association));
                if (\is_array($item)) {
                    $this->addMeta($value, $item, $association->getTargetMetadata());
                }
                $this->addLinks($value, $association->getLinks());
            }
            $relationshipValue[self::DATA] = $value;
            $result[self::RELATIONSHIPS][$name] = $relationshipValue;
        }
    }

    protected function addAssociationMeta(array &$result, ?array $meta): void
    {
        if (!empty($meta)) {
            $result[self::META] = $meta;
        }
    }

    protected function getAssociationData(RequestType $requestType, mixed $item, string $itemId): array
    {
        if (!\is_array($item)) {
            $item = [self::ID => $item];
        } elseif (!empty($item[DataAccessorInterface::ENTITY_CLASS])
            && !\array_key_exists(DataAccessorInterface::ENTITY_TYPE, $item)
        ) {
            $item[DataAccessorInterface::ENTITY_TYPE] = $this->getEntityAlias(
                $item[DataAccessorInterface::ENTITY_CLASS],
                $requestType
            );
        }
        $item[DataAccessorInterface::ENTITY_ID] = $itemId;

        return $item;
    }

    protected function addAttributesAsMeta(array &$result, array $data, EntityMetadata $metadata): void
    {
        $fields = $metadata->getFields();
        foreach ($fields as $name => $field) {
            if (!$field->isOutput()) {
                continue;
            }
            $result[self::META][$name] = $data[$name] ?? null;
        }
    }

    protected function addRelationshipsAsMeta(
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
            $result[self::META][$name] = $this->getRelationshipValue($data, $requestType, $name, $association);
        }
    }

    #[\Override]
    protected function processRelatedObject(
        mixed $object,
        RequestType $requestType,
        AssociationMetadata $associationMetadata
    ): mixed {
        $this->resultDataAccessor->addEntity();
        try {
            $targetMetadata = $this->getTargetMetadataProvider()
                ->getAssociationTargetMetadata($object, $associationMetadata);
            $preparedValue = $this->prepareRelatedValue(
                $object,
                $requestType,
                $associationMetadata->getTargetClassName(),
                $targetMetadata
            );
            if ($preparedValue['idOnly']) {
                $resourceId = $this->getResourceIdObject(
                    $preparedValue['entityType'],
                    $this->getEntityIdTransformer($requestType)->transform($preparedValue['value'], $targetMetadata)
                );
            } else {
                $targetObject = $preparedValue['value'];
                $resourceId = $this->getResourceIdObject(
                    $preparedValue['entityType'],
                    $this->getEntityId($targetObject, $requestType, $targetMetadata)
                );
                $this->addRelatedObject($this->convertObjectToArray($targetObject, $requestType, $targetMetadata));
            }

            return $resourceId;
        } finally {
            $this->resultDataAccessor->removeLastEntity();
        }
    }

    protected function prepareRelatedValue(
        mixed $object,
        RequestType $requestType,
        string $targetClassName,
        ?EntityMetadata $targetMetadata
    ): array {
        $idOnly = false;
        $targetEntityType = null;
        if (\is_array($object) || \is_object($object)) {
            if (null !== $targetMetadata) {
                $objectClassName = $this->objectAccessor->getClassName($object);
                if ($objectClassName) {
                    $targetEntityType = $this->getEntityType($objectClassName, $requestType, $targetClassName);
                }

                if ($this->hasIdentifierFieldsOnly($targetMetadata)) {
                    $idOnly = true;
                    $idFieldNames = $targetMetadata->getIdentifierFieldNames();
                    if (\count($idFieldNames) === 1) {
                        $object = $this->objectAccessor->getValue($object, reset($idFieldNames));
                    } else {
                        $data = [];
                        foreach ($idFieldNames as $fieldName) {
                            $data[$fieldName] = $this->objectAccessor->getValue($object, $fieldName);
                        }
                        $object = $data;
                    }
                }
            }
        } else {
            $idOnly = true;
        }
        if (!$targetEntityType) {
            $targetEntityType = $this->getEntityType($targetClassName, $requestType);
        }

        return [
            'value'      => $object,
            'entityType' => $targetEntityType,
            'idOnly'     => $idOnly
        ];
    }

    #[\Override]
    protected function addRelatedObject(array $object): void
    {
        // check whether this object was already added
        if (!empty($this->result[self::INCLUDED])) {
            $entityType = $object[self::TYPE];
            $entityId = $object[self::ID];
            foreach ($this->result[self::INCLUDED] as $existingObject) {
                if ($existingObject[self::TYPE] === $entityType && $existingObject[self::ID] === $entityId) {
                    return;
                }
            }
        }

        $this->result[self::INCLUDED][] = $object;
    }

    protected function getResourceIdObject(string $entityType, string $entityId): array
    {
        return [
            self::TYPE => $entityType,
            self::ID   => $entityId
        ];
    }

    protected function getLinkObject(string $href, ?array $properties): array|string
    {
        if (empty($properties)) {
            return $href;
        }

        return [self::HREF => $href, self::META => $properties];
    }

    private function removeRelatedObjectsThatDuplicatePrimaryObjects(bool $collection): void
    {
        if (empty($this->result[self::INCLUDED])) {
            return;
        }

        $primaryEntities = [];
        if ($collection) {
            foreach ($this->result[self::DATA] as $item) {
                $primaryEntities[$item[self::TYPE]][$item[self::ID]] = true;
            }
        } else {
            $item = $this->result[self::DATA];
            $primaryEntities[$item[self::TYPE]][$item[self::ID]] = true;
        }
        $toRemoveKeys = [];
        foreach ($this->result[self::INCLUDED] as $key => $item) {
            if (isset($primaryEntities[$item[self::TYPE]][$item[self::ID]])) {
                $toRemoveKeys[] = $key;
            }
        }
        if ($toRemoveKeys) {
            foreach ($toRemoveKeys as $key) {
                unset($this->result[self::INCLUDED][$key]);
            }
            $this->result[self::INCLUDED] = array_values($this->result[self::INCLUDED]);
        }
    }
}
