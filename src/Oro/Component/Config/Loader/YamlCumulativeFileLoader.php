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
     * @param string[] $importedPaths already parsed paths, for circular check
     * @return array|null
     * @throws \InvalidArgumentException When loading of YAML file returns error
     */
    protected function parseFile($file, $importedPaths = [])
    {
        try {
            $configData = Yaml::parse(file_get_contents($file)) ?: [];

            if (array_key_exists('imports', $configData) && is_array($configData['imports'])) {
                if (empty($importedPaths)) {
                    $importedPaths[] = $file; // for checking circular own-import
                }

                $imports = $configData['imports'];
                unset($configData['imports']);

                foreach ($imports as $importData) {
                    if (array_key_exists('resource', $importData)) {
                        $parent = new \SplFileInfo($file);
                        $import = new \SplFileInfo($parent->getPath() . DIRECTORY_SEPARATOR . $importData['resource']);
                        $importPath = $import->getRealPath();

                        if (in_array($importPath, $importedPaths, true)) {
                            $importedPaths[] = $importPath; // for a complete tree in the message

                            throw new \InvalidArgumentException(
                                sprintf('Circular import detected in for "%s".', implode(' >> ', $importedPaths))
                            );
                        }

                        $importedPaths[] = $importPath;
                        $configData = array_merge_recursive($configData, $this->parseFile($importPath, $importedPaths));
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
