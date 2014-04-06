<?php

namespace Oro\Component\Config\Loader;

class CumulativeResourceLoaderResolver
{
    /**
     * @param mixed $resource
     * @throws \RuntimeException
     * @return CumulativeResourceLoader
     */
    public function resolve($resource)
    {
        if (is_string($resource)) {
            if ($this->strEndsWith($resource, '.yml')) {
                return new YamlCumulativeFileLoader($resource);
            }
        }

        throw new \RuntimeException(sprintf('Cannot resolve a loader for "%s".', (string)$resource));
    }

    /**
     * Checks if the given string ends with $needle
     *
     * @param string $str
     * @param string $needle
     * @return bool
     */
    protected function strEndsWith($str, $needle)
    {
        return substr($str, -strlen($needle)) == $needle;
    }
}
