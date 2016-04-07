<?php

namespace Oro\Bundle\PlatformBundle\Yaml;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml as YamlOrigin;
use Symfony\Component\Yaml\Exception\ParseException;

class Yaml extends YamlOrigin
{
    /**
     * @inheritdoc
     * Remove trigger_error when passing a file as an input
     */
    public static function parse($input, $exceptionOnInvalidType = false, $objectSupport = false, $objectForMap = false)
    {
        $file = '';
        if (strpos($input, "\n") === false && is_file($input)) {
            if (false === is_readable($input)) {
                throw new ParseException(sprintf('Unable to parse "%s" as the file is not readable.', $input));
            }

            $file = $input;
            $input = file_get_contents($file);
        }

        $yaml = new Parser();

        try {
            return $yaml->parse($input, $exceptionOnInvalidType, $objectSupport, $objectForMap);
        } catch (ParseException $e) {
            if ($file) {
                $e->setParsedFile($file);
            }

            throw $e;
        }
    }
}
