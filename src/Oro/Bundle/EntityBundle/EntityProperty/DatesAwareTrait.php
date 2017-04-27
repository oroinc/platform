<?php

namespace Oro\Bundle\EntityBundle\EntityProperty;

use JMS\Serializer\Annotation as Serializer;

trait DatesAwareTrait
{
    use CreatedAtAwareTrait;
    use UpdatedAtAwareTrait;
}
