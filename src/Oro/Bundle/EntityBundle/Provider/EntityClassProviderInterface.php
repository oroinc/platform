<?php

namespace Oro\Bundle\EntityBundle\Provider;

interface EntityClassProviderInterface
{
    /**
     * Returns a list of entity class names.
     *
     * @return string[]
     */
    public function getClassNames();
}
