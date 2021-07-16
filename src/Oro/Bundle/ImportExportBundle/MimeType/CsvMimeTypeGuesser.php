<?php

namespace Oro\Bundle\ImportExportBundle\MimeType;

use Symfony\Component\Mime\FileinfoMimeTypeGuesser;

/**
 * Guess CSV mime type for a given path.
 */
class CsvMimeTypeGuesser extends FileinfoMimeTypeGuesser
{
    private const MIME_TYPE = 'text/csv';
    private const EXTENSION = 'csv';

    /**
     * @var string
     */
    private $delimiter = ',';

    /**
     * @var string
     */
    private $enclosure = '"';

    /**
     * @var string
     */
    private $escape = '\\';

    /**
     * {@inheritDoc}
     */
    public function guessMimeType(string $path): ?string
    {
        // Guess for files with csv extension only
        if (UploadedFileExtensionHelper::getExtensionByPath($path) !== self::EXTENSION) {
            return null;
        }

        // Guess MIME type with FileinfoMimeTypeGuesser. For text/csv return, no need to guess further.
        $mimeType = parent::guessMimeType($path);
        if ($mimeType === self::MIME_TYPE) {
            return $mimeType;
        }

        // If the file has a CSV extension but wasn't detected as text/csv - try to read first 2 lines with fgetcsv.
        // For CSV file both lines should be arrays with same number of rows.
        // This covers case with text/html detection, because expected CSV file should have header in the first line.
        // Incorrect detection is caused by HTML content in one of the lines below the header,
        // that's why we expect the file to contain at least 2 lines.
        $fh = fopen($path, 'r');
        $line1 = fgetcsv($fh, 0, $this->delimiter, $this->enclosure, $this->escape);
        if (!is_array($line1)) {
            return null;
        }
        $line2 = fgetcsv($fh, 0, $this->delimiter, $this->enclosure, $this->escape);
        if (!is_array($line2)) {
            return null;
        }
        fclose($fh);

        if (count($line1) === count($line2)) {
            return self::MIME_TYPE;
        }

        return null;
    }

    public function setDelimiter(string $delimiter)
    {
        $this->delimiter = $delimiter;
    }

    public function setEnclosure(string $enclosure)
    {
        $this->enclosure = $enclosure;
    }

    public function setEscape(string $escape)
    {
        $this->escape = $escape;
    }
}
