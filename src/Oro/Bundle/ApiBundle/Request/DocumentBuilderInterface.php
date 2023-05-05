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
     */
    public function getDocument(): array;

    /**
     * Removes all data from the document.
     */
    public function clear(): void;

    /**
     * Gets the type of the given entity.
     */
    public function getEntityAlias(string $entityClass, RequestType $requestType): ?string;

    /**
     * Gets a string representation of the given entity identifier.
     */
    public function getEntityId(mixed $entity, RequestType $requestType, EntityMetadata $metadata): string;

    /**
     * Sets metadata that are linked to data.
     *
     * @param array $metadata [data item path => [key => value, ...], ...]
     */
    public function setMetadata(array $metadata): void;

    /**
     * Sets a single object as the primary data.
     */
    public function setDataObject(mixed $object, RequestType $requestType, ?EntityMetadata $metadata): void;

    /**
     * Sets a collection as the primary data.
     */
    public function setDataCollection(mixed $collection, RequestType $requestType, ?EntityMetadata $metadata): void;

    /**
     * Adds an object related to the primary data.
     * E.g. in JSON:API this object is added to the "included" section.
     * @link http://jsonapi.org/format/#fetching-includes
     */
    public function addIncludedObject(mixed $object, RequestType $requestType, ?EntityMetadata $metadata): void;

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
     */
    public function addLinkMetadata(string $name, LinkMetadataInterface $link): void;

    /**
     * Sets an error.
     */
    public function setErrorObject(Error $error): void;

    /**
     * Sets errors collection.
     *
     * @param Error[] $errors
     */
    public function setErrorCollection(array $errors): void;
}
