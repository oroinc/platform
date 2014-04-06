<?php

namespace Oro\Component\Config\Loader;

use Symfony\Component\Yaml\Yaml;

class YamlCumulativeFileLoader extends CumulativeFileLoader
{
    /**
     * {@inheritdoc}
     */
    protected function loadFile($path)
    {
        return Yaml::parse($path);
    }
}
