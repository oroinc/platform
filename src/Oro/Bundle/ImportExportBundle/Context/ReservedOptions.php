<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Context;

/**
 * Job configuration keys that belong to the server and must never be supplied by a caller.
 */
final class ReservedOptions
{
    public const OPTIONS = [
        Context::OPTION_FILE_PATH,
        Context::OPTION_DELIMITER,
        Context::OPTION_ENCLOSURE,
        Context::OPTION_ESCAPE,
        Context::OPTION_HEADER,
    ];

    /**
     * Returns the reserved keys present in the given options.
     *
     * @return string[]
     */
    public static function detect(array $options): array
    {
        return array_values(array_intersect(array_keys($options), self::OPTIONS));
    }
}
