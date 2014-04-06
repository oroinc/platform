<?php

namespace Oro\Component\Config\Loader;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

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
        $yamlParser = new YamlParser();
        try {
            return $yamlParser->parse(file_get_contents($file));
        } catch (ParseException $e) {
            $e->setParsedFile($file);
            throw new \InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }
    }
}
