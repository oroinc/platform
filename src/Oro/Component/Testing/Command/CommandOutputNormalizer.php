<?php

namespace Oro\Component\Testing\Command;

use Symfony\Component\Console\Tester\CommandTester;

/**
 * Normalizes command output
 */
class CommandOutputNormalizer
{
    /**
     * Converts a multi-line string or an array of string into a single line string.
     * Reduces multiple spaces (PCRE [[:space:]]+) to a single space(32) character.
     *
     * @param string|string[]|CommandTester $output
     * @return string
     */
    public static function toSingleLine($output): string
    {
        if ($output instanceof CommandTester) {
            $result = $output->getDisplay();
        } elseif (\is_array($output)) {
            $result = \join(' ', $output);
        } else {
            $result = \strval($output);
        }

        return \trim(\preg_replace('/[[:space:]]+/', ' ', $result));
    }
}
