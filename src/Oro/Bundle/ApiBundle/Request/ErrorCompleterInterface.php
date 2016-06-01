<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;

interface ErrorCompleterInterface
{
    /**
     * Completes all properties of a given Error object.
     *
     * @param Error               $error
     * @param EntityMetadata|null $metadata
     */
    public function complete(Error $error, EntityMetadata $metadata = null);
}
