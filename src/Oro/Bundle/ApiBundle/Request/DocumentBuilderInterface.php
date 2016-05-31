<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;

interface DocumentBuilderInterface
{
    /**
     * Returns built document.
     *
     * @return array
     */
    public function getDocument();

    /**
     * Removes all data from the document.
     */
    public function clear();

    /**
     * Sets a single object as the primary data.
     *
     * @param mixed               $object
     * @param EntityMetadata|null $metadata
     */
    public function setDataObject($object, EntityMetadata $metadata = null);

    /**
     * Sets a collection as the primary data.
     *
     * @param mixed               $collection
     * @param EntityMetadata|null $metadata
     */
    public function setDataCollection($collection, EntityMetadata $metadata = null);

    /**
     * Sets an error.
     *
     * @param Error          $error
     * @param EntityMetadata $metadata
     */
    public function setErrorObject(Error $error, EntityMetadata $metadata = null);

    /**
     * Sets errors collection.
     *
     * @param Error[]        $errors
     * @param EntityMetadata $metadata
     */
    public function setErrorCollection(array $errors, EntityMetadata $metadata = null);
}
