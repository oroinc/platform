<?php

namespace Oro\Bundle\TranslationBundle\Provider;

class YamlFixer
{
    /**
     * @param $target
     */
    public static function fixStrings($target)
    {
        $contents = file($target);

        // remove first dashes line
        if (isset($contents[0]) && trim($contents[0]) == '---') {
            array_shift($contents);
        }

        $isMultiLine = false;

        // fix downloaded translations
        foreach ($contents as $i => $line) {
            $line = explode(':', $line);

            if (!isset($line[0]) || count($line) != 2 || $isMultiLine) {
                continue;
            }

            $key = $line[0];
            if (in_array($key[0], ['"', "'"])) {
                $lineQuote = $key[0];
            } else {
                $lineQuote = '';
            }

            // check if it's starting multiline string
            $isMultiLine = trim($line[1])[0] == '|';

            $key = trim($key, '"\'');
            if (strpos($key, '"') !== false) {
                $key = "'" . $key . "'";
            } else {
                $key = $lineQuote . $key . $lineQuote;
            }
            $contents[$i] = $key . ':' . $line[1];
        }

        file_put_contents(
            $target,
            trim(implode('', $contents))
        );
    }
}
