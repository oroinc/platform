<?php

namespace Oro\Bundle\EntityBundle\EntityProperty;

/**
 * Add create and update dates support to entities
 */
trait DatesAwareTrait
{
    use CreatedAtAwareTrait;
    use UpdatedAtAwareTrait;
}
