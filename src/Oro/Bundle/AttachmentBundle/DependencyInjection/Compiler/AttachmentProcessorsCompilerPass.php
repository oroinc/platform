<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection\Compiler;

use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Responsible for configurations of pngquant, jpegoptim libraries.
 */
class AttachmentProcessorsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $pngquantBinaryPath = $this->getResolvedBinaryPath($container, ProcessorHelper::PNGQUANT);
        $jpegoptimBinaryPath =  $this->getResolvedBinaryPath($container, ProcessorHelper::JPEGOPTIM);

        $isLibrariesExists = $this->isLibraryExist(ProcessorHelper::PNGQUANT, $pngquantBinaryPath) &&
            $this->isLibraryExist(ProcessorHelper::JPEGOPTIM, $jpegoptimBinaryPath);

        $container->setParameter('oro_attachment.post_processors.enabled', $isLibrariesExists);

        if ($isLibrariesExists) {
            $container->getDefinition('liip_imagine.filter.post_processor.pngquant')
                ->setArgument(
                    '$executablePath',
                    $pngquantBinaryPath
                );
            $container->getDefinition('liip_imagine.filter.post_processor.jpegoptim')
                ->setArgument(
                    '$executablePath',
                    $jpegoptimBinaryPath
                );
        }
    }

    private function isLibraryExist(string $name, string $binary): bool
    {
        try {
            $isLibraryExists = (bool)ProcessorHelper::getBinary($name, $binary);
        } catch (\Exception $e) {
            $isLibraryExists = false;
        }

        return $isLibraryExists;
    }

    private function getResolvedBinaryPath(ContainerBuilder $container, string $binaryName): ?string
    {
        $parameterName = sprintf('liip_imagine.%s.binary', $binaryName);
        return $container->resolveEnvPlaceholders($container->getParameter($parameterName), true) ?:
            ProcessorHelper::findBinary($binaryName);
    }
}
