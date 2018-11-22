<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\LinkMetadataInterface;
use Oro\Bundle\ApiBundle\Model\Error;

/**
 * Provides an interface for different kind of response document builders.
 */
interface DocumentBuilderInterface
{
    /**
     * Returns built document.
     *
     * @return array
     */
    public function getDocument(): array;

    /**
     * Removes all data from the document.
     */
    public function clear(): void;

    /**
     * Gets the type of the given entity.
     *
     * @param string      $entityClass
     * @param RequestType $requestType
     *
     * @return string|null
     */
    public function getEntityAlias(string $entityClass, RequestType $requestType): ?string;

    /**
     * Gets a string representation of the given entity identifier.
     *
     * @param mixed          $entity
     * @param RequestType    $requestType
     * @param EntityMetadata $metadata
     *
     * @return string
     */
    public function getEntityId($entity, RequestType $requestType, EntityMetadata $metadata): string;

    /**
     * Sets metadata that are linked to data.
     *
     * @param array $metadata [data item path => [key => value, ...], ...]
     */
    public function setMetadata(array $metadata): void;

    /**
     * Sets a single object as the primary data.
     *
     * @param mixed               $object
     * @param RequestType         $requestType
     * @param EntityMetadata|null $metadata
     */
    public function setDataObject($object, RequestType $requestType, EntityMetadata $metadata = null): void;

    /**
     * Sets a collection as the primary data.
     *
     * @param mixed               $collection
     * @param RequestType         $requestType
     * @param EntityMetadata|null $metadata
     */
    public function setDataCollection($collection, RequestType $requestType, EntityMetadata $metadata = null): void;

    /**
     * Adds an object related to the primary data.
     * E.g. in JSON.API this object is added to the "included" section.
     * @link http://jsonapi.org/format/#fetching-includes
     *
     * @param mixed               $object
     * @param RequestType         $requestType
     * @param EntityMetadata|null $metadata
     */
    public function addIncludedObject($object, RequestType $requestType, EntityMetadata $metadata = null): void;

    /**
     * Adds a link for a whole document data.
     *
     * @param string $name
     * @param string $href
     * @param array  $properties [name => scalar value]
     */
    public function addLink(string $name, string $href, array $properties = []): void;

    /**
     * Adds a link for a whole document data.
     *
     * @param string                $name
     * @param LinkMetadataInterface $link
     */
    public function addLinkMetadata(string $name, LinkMetadataInterface $link): void;

    /**
     * Sets an error.
     *
     * @param Error $error
     */
    public function setErrorObject(Error $error): void;

    /**
     * Sets errors collection.
     *
     * @param Error[] $errors
     */
    public function setErrorCollection(array $errors): void;
}
