<?php

namespace Oro\Bundle\ApiBundle\Util;

/**
 * Provides functionality to compress and decompress OpenAPI specification.
 */
class OpenApiSpecificationArchive
{
    private const SPECIFICATION_FILE_NAME = 'specification.txt';

    public function compress(string $specification): string
    {
        $tmpFile = tmpfile();
        if (false === $tmpFile) {
            throw new \RuntimeException(
                'The compressing of the OpenAPI specification failed. Cannot create a temporary file.'
            );
        }
        try {
            return $this->compressSpecification(stream_get_meta_data($tmpFile)['uri'], $specification);
        } catch (\Throwable $e) {
            throw new \RuntimeException('The compressing of the OpenAPI specification failed.', $e->getCode(), $e);
        } finally {
            fclose($tmpFile);
        }
    }

    public function decompress(string $compressedSpecification): string
    {
        $tmpFile = tmpfile();
        if (false === $tmpFile) {
            throw new \RuntimeException(
                'The decompressing of the OpenAPI specification failed. Cannot create a temporary file.'
            );
        }
        try {
            return $this->decompressSpecification(stream_get_meta_data($tmpFile)['uri'], $compressedSpecification);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'The decompressing of the OpenAPI specification failed.',
                $e->getCode(),
                $e
            );
        } finally {
            fclose($tmpFile);
        }
    }

    private function compressSpecification(string $tmpFileName, string $specification): string
    {
        unlink($tmpFileName);
        $zip = new \ZipArchive();
        $openResult = $zip->open($tmpFileName, \ZipArchive::CREATE);
        if (true !== $openResult) {
            throw new \RuntimeException(
                'Cannot open ZIP archive.'
                . (false !== $openResult ? sprintf(' Error code: %d.', $openResult) : '')
            );
        }
        if (!$zip->addFromString(self::SPECIFICATION_FILE_NAME, $specification)) {
            throw new \RuntimeException('Cannot add the specification file to ZIP archive.');
        }
        if (!$zip->close()) {
            throw new \RuntimeException('Cannot close ZIP archive.');
        }
        $compressedSpecification = file_get_contents($tmpFileName);
        if (false === $compressedSpecification) {
            throw new \RuntimeException('Failed read data from a temporary file.');
        }

        return base64_encode($compressedSpecification);
    }

    private function decompressSpecification(string $tmpFileName, string $compressedSpecification): string
    {
        $decodedSpecification = base64_decode($compressedSpecification);
        if (false === $decodedSpecification) {
            throw new \RuntimeException('Failed decode data.');
        }
        if (false === file_put_contents($tmpFileName, $decodedSpecification)) {
            throw new \RuntimeException('Failed write data to a temporary file.');
        }
        $zip = new \ZipArchive();
        $openResult = $zip->open($tmpFileName);
        if (true !== $openResult) {
            throw new \RuntimeException(
                'Cannot open ZIP archive.'
                . (false !== $openResult ? sprintf(' Error code: %d.', $openResult) : '')
            );
        }
        $specification = $zip->getFromName(self::SPECIFICATION_FILE_NAME);
        if (false === $specification) {
            throw new \RuntimeException('Failed read data from ZIP archive.');
        }
        if (!$zip->close()) {
            throw new \RuntimeException('Cannot close ZIP archive.');
        }

        return $specification;
    }
}
