<?php

namespace Oro\Bundle\AttachmentBundle;

use Oro\Bundle\AttachmentBundle\Exception\ProcessorsException;
use Oro\Bundle\AttachmentBundle\Exception\ProcessorsVersionException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * An auxiliary class that finds libraries and validate it.
 */
class ProcessorHelper
{
    public const PNGQUANT = 'pngquant';
    public const JPEGOPTIM = 'jpegoptim';

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function librariesExists(): bool
    {
        return $this->getPNGQuantLibrary() && $this->getJPEGOptimLibrary();
    }

    public function getPNGQuantLibrary(): ?string
    {
        return self::getBinary(self::PNGQUANT, $this->getParameter(self::PNGQUANT));
    }

    public function getJPEGOptimLibrary(): ?string
    {
        return self::getBinary(self::JPEGOPTIM, $this->getParameter(self::JPEGOPTIM));
    }

    public static function getBinary(string $name, ?string $binary): ?string
    {
        if (empty($binary)) {
            return null;
        }

        if (!is_executable($binary)) {
            throw new ProcessorsException($name);
        }

        if (!ProcessorVersionChecker::satisfies($binary)) {
            [$name, $version] = ProcessorVersionChecker::getLibraryInfo($name);
            throw new ProcessorsVersionException($name, $version, $binary);
        }

        return $binary;
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

    public function generateParameter(string $name): string
    {
        return sprintf('liip_imagine.%s.binary', $name);
    }

    private function getParameter(string $name): ?string
    {
        $parameter = $this->generateParameter($name);
        return $this->parameterBag->get($parameter);
    }
}
