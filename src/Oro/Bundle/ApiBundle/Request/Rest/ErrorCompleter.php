<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractErrorCompleter;

class ErrorCompleter extends AbstractErrorCompleter
{
    /**
     * {@inheritdoc}
     */
    public function complete(Error $error, EntityMetadata $metadata = null)
    {
        $this->completeStatusCode($error);
        $this->completeCode($error);
        $this->completeTitle($error);
        $this->completeDetail($error);
    }
}
