<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This interface can be implemented by describers that needs {@see ErrorResponseStorage}.
 */
interface ErrorResponseStorageAwareInterface
{
    public function setErrorResponseStorage(?ErrorResponseStorage $errorResponseStorage): void;
}
