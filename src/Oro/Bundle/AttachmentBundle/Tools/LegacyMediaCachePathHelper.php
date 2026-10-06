<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

/**
 * Resolves the legacy path of a media cache file.
 *
 * Media cache files used to be stored under the percent-encoded path produced by the URL generator,
 * now they are stored under the URL-decoded path. The legacy path is required to serve and to remove
 * the files stored before this change.
 */
final class LegacyMediaCachePathHelper
{
    /**
     * The characters that the Symfony URL generator leaves unencoded in a generated path.
     * @see \Symfony\Component\Routing\Generator\UrlGenerator::$decodedChars
     */
    private const DECODED_CHARS = [
        '%2F' => '/',
        '%40' => '@',
        '%3A' => ':',
        '%3B' => ';',
        '%2C' => ',',
        '%3D' => '=',
        '%2B' => '+',
        '%21' => '!',
        '%2A' => '*',
        '%7C' => '|',
    ];

    /**
     * Returns the legacy (percent-encoded) path for the given media cache path.
     * The returned path equals the given one when the path contains no characters to encode.
     */
    public static function getLegacyPath(string $path): string
    {
        return strtr(rawurlencode($path), self::DECODED_CHARS);
    }
}
