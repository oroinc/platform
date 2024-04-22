<?php

namespace Oro\Bundle\AttachmentBundle;

use Oro\Bundle\AttachmentBundle\Exception\ProcessorsException;
use Oro\Bundle\AttachmentBundle\Exception\ProcessorsVersionException;

/**
 * An auxiliary class that finds libraries and validate it.
 */
class ProcessorHelper
{
    public const PNGQUANT = 'pngquant';
    public const JPEGOPTIM = 'jpegoptim';

    private string $pngquantBinaryPath;
    private string $jpegoptimBinaryPath;

    public function __construct(
        string $jpegoptimBinaryPath,
        string $pngquantBinaryPath
    ) {
        $this->jpegoptimBinaryPath = $jpegoptimBinaryPath;
        $this->pngquantBinaryPath = $pngquantBinaryPath;
    }

    public function librariesExists(): bool
    {
        return $this->getPNGQuantLibrary() && $this->getJPEGOptimLibrary();
    }

    public function getPNGQuantLibrary(): ?string
    {
        return self::getLibrary(self::PNGQUANT, $this->pngquantBinaryPath);
    }

    public function getJPEGOptimLibrary(): ?string
    {
        return self::getLibrary(self::JPEGOPTIM, $this->jpegoptimBinaryPath);
    }

    public static function getLibrary(string $name, string $binary): ?string
    {
        if (!in_array($name, [self::JPEGOPTIM, self::PNGQUANT])) {
            throw new \InvalidArgumentException(sprintf('Library %s is not supported.', $name));
        }

        if (!empty($binary)) {
            if (!is_executable($binary)) {
                throw new ProcessorsException($name);
            }

            if (!ProcessorVersionChecker::satisfies($binary)) {
                [$name, $version] = ProcessorVersionChecker::getLibraryInfo($name);
                throw new ProcessorsVersionException($name, $version, $binary);
            }

            return $binary;
        }

        return null;
    }

    public static function findLibrary(string $name): ?string
    {
        $processorExecutableFinder = new ProcessorExecutableFinder();

        $binary = $processorExecutableFinder->find($name);
        if ($binary && is_executable($binary) && ProcessorVersionChecker::satisfies($binary)) {
            return $binary;
        }

        return null;
    }
}
