<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

/**
 * Sanitize filename to contain only alphanumeric characters, -, _
 */
class FilenameSanitizer
{
    /**
     * @param string $fileName
     * @return string
     */
    public static function sanitizeFilename(string $fileName): string
    {
        return trim(
            str_replace(
                '-.',
                '.',
                mb_ereg_replace('[^\w|\.]+', '-', $fileName)
            ),
            '-'
        );
    }
}
