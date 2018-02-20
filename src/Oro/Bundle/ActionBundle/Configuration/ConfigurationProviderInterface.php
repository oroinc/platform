<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

interface ConfigurationProviderInterface
{
    /**
     * Warm up configuration cache
     */
    public function warmUpCache();

    /**
     * Clear configuration cache
     */
    public function clearCache();

    /**
     * @param bool $ignoreCache
     * @param Collection $errors
     * @return array
     * @throws InvalidConfigurationException
     */
    public function getConfiguration($ignoreCache = false, Collection $errors = null);
}
