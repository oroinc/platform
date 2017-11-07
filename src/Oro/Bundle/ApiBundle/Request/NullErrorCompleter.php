<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;

/**
 * Keeps all all properties of Error objects as is.
 */
class NullErrorCompleter implements ErrorCompleterInterface
{
    /**
     * {@inheritdoc}
     */
    public function complete(Error $error, EntityMetadata $metadata = null)
    {
    }
}
