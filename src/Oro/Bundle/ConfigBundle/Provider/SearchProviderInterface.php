<?php

namespace Oro\Bundle\ConfigBundle\Provider;

interface SearchProviderInterface
{
    /**
     * Determines whether this provider is applicable for the given name
     *
     * @param string $name
     *
     * @return bool
     */
    public function supports($name);

    /**
     * Returns configuration search data by given name
     *
     * @param string $name
     *
     * @return array
     */
    public function getData($name);
}
