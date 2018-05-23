<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\FixErrorPathIncludedEntityTrait;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\CollectFormErrors as BaseCollectFormErrors;

/**
 * Collects errors occurred during submit of forms for primary and included entities
 * and adds them into the context.
 */
class CollectFormErrors extends BaseCollectFormErrors
{
    use FixErrorPathIncludedEntityTrait;
}
