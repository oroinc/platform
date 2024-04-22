<?php

namespace Oro\Bundle\AttachmentBundle;

use Oro\Bundle\AttachmentBundle\Exception\ProcessorsException;
use Oro\Bundle\AttachmentBundle\Exception\ProcessorsVersionException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * An auxiliary class that finds libraries and validate it.
 */
class ProcessorHelper
{
    public const PNGQUANT = 'pngquant';
    public const JPEGOPTIM = 'jpegoptim';

    private string $pngquantBinaryPath;
    private string $jpegoptimBinaryPath;
    private CacheInterface $cache;

    public function __construct(
        string $jpegoptimBinaryPath,
        string $pngquantBinaryPath,
        CacheInterface $cache
    ) {
        $this->jpegoptimBinaryPath = $jpegoptimBinaryPath;
        $this->pngquantBinaryPath = $pngquantBinaryPath;
        $this->cache = $cache;
    }

    public function librariesExists(): bool
    {
        return $this->getPNGQuantLibrary() && $this->getJPEGOptimLibrary();
    }

    public function getPNGQuantLibrary(): ?string
    {
        return self::getBinary(self::PNGQUANT, $this->pngquantBinaryPath);
    }

    public function getJPEGOptimLibrary(): ?string
    {
        return self::getBinary(self::JPEGOPTIM, $this->jpegoptimBinaryPath);
    }

    public static function getBinary(string $name, string $binary): ?string
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

    public static function findBinary(string $name): ?string
    {
        $processorExecutableFinder = new ProcessorExecutableFinder();

        $binary = $processorExecutableFinder->find($name);
        if ($binary && is_executable($binary) && ProcessorVersionChecker::satisfies($binary)) {
            return $binary;
        }

        return null;
    }
}
