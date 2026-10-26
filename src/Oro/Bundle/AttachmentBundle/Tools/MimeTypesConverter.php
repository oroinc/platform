<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

/**
 * Provides static methods to convert the list of MIME types
 * from a string contains MIME types delimited by linefeed (\n),
 * carriage return + linefeed (\r\n) or comma (,) symbols
 * to an array and vise versa.
 * Linefeed (\n) is used as a delimiter for converting from an array to a string.
 */
final class MimeTypesConverter
{
    /**
     * Converts the list of MIME types from an array to a string representation.
     *
     * @param string[] $mimeTypes
     *
     * @return string
     */
    public static function convertToString(array $mimeTypes): string
    {
        return implode("\n", $mimeTypes);
    }

    /**
     * Converts the list of MIME types from a string representation to an array.
     *
     * @param string|null $mimeTypes
     *
     * @return string[]
     */
    public static function convertToArray(?string $mimeTypes): array
    {
        if (!$mimeTypes) {
            return [];
        }

        return array_map('trim', explode("\n", str_replace(',', "\n", str_replace("\r", '', $mimeTypes))));
    }

    /**
     * Reduces a MIME type to its media type: the lower-case "type/subtype" without parameters.
     */
    public static function convertToMediaType(string $mimeType): string
    {
        return strtolower(trim(explode(';', $mimeType, 2)[0]));
    }

    /**
     * Checks whether a value is a media type, the lower-case "type/subtype" without parameters.
     */
    public static function isMediaType(string $value): bool
    {
        return 1 === preg_match('/^[a-z0-9!#$&^_.+-]+\/[a-z0-9!#$&^_.+-]+$/D', $value);
    }
}
