<?php

namespace Oro\Bundle\CronBundle\ORM;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * This normalizer is applicable for databases where command arguments are stored as a text data type
 */
class CommandArgsNormalizer
{
    /**
     * Indicates whether this normalizer can be used for a given database platform.
     *
     * @param AbstractPlatform $platform
     *
     * @return bool
     */
    public function supports(AbstractPlatform $platform)
    {
        return true;
    }

    /**
     * Normalizes a string value
     *
     * @param string $value
     *
     * @return string
     */
    public function normalize($value)
    {
        return str_replace('\\', '\\\\\\\\', $value);
    }

    /**
     * Quotes a command argument
     *
     * @param string $value
     *
     * @return string
     */
    public function quoteArg($value)
    {
        return '\\\\\\"' . $value . '\\\\\\"';
    }

    /**
     * Quotes a value of a command argument
     *
     * @param string $value
     *
     * @return string
     */
    public function quoteArgValue($value)
    {
        return '\\\\"' . $value . '\\\\"';
    }
}
