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
        return $this->getLibrary(self::PNGQUANT) ?? $this->findLibrary(self::PNGQUANT);
    }

    public function getJPEGOptimLibrary(): ?string
    {
        return $this->getLibrary(self::JPEGOPTIM) ?? $this->findLibrary(self::JPEGOPTIM);
    }

    private function getLibrary($name): ?string
    {
        $binary = null;
        $parameter = $this->generateParameter($name);
        # parameter may be null or an empty string
        if (!empty($this->parameterBag->get($parameter))) {
            $binary = $this->parameterBag->get($parameter);
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

    private function findLibrary(string $name): ?string
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
}
