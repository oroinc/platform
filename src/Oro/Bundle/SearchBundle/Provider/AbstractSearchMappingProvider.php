<?php

namespace Oro\Bundle\SearchBundle\Provider;

abstract class AbstractSearchMappingProvider
{
    /**
     * @return array
     */
    abstract public function getMappingConfig();
}
