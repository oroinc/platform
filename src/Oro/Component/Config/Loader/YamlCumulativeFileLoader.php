<?php

namespace Oro\Component\Config\Loader;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

// TODO: Override CumulativeFileLoader::isResourceFresh to check also imported files
class YamlCumulativeFileLoader extends CumulativeFileLoader
{
    /**
     * {@inheritdoc}
     */
    protected function loadFile($file)
    {
        return $this->parseFile($file);
    }

    /**
     * Parses a YAML file.
     *
     * @param string $file Path to a file
     * @return array|null
     * @throws \InvalidArgumentException When loading of YAML file returns error
     */
    protected function parseFile($file)
    {
        try {
            $configData = Yaml::parse(file_get_contents($file)) ? : [];

            if (array_key_exists('imports', $configData) && is_array($configData['imports'])) {
                $imports = $configData['imports'];
                unset($configData['imports']);

                foreach ($imports as $importData) {
                    if (array_key_exists('resource', $importData)) {
                        $info = new \SplFileInfo($file);
                        $import = new \SplFileInfo($info->getPath() . DIRECTORY_SEPARATOR . $importData['resource']);
                        $configData = array_merge_recursive($configData, $this->parseFile($import));
                    }
                }
            }

            return $configData;
        } catch (ParseException $e) {
            $e->setParsedFile($file);
            throw new \InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }
    }
}
