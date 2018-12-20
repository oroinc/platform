<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\ProcessIncludedEntities as BaseProcessIncludedEntities;

/**
 * Validates and fill included entities.
 */
class ProcessIncludedEntities extends BaseProcessIncludedEntities
{
    use FixErrorPathIncludedEntityTrait;
}
