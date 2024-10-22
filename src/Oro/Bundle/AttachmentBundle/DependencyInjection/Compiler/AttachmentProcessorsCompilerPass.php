<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection\Compiler;

use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Responsible for configurations of pngquant, jpegoptim libraries.
 */
class AttachmentProcessorsCompilerPass implements CompilerPassInterface
{
    private const PNGQUANT_POST_PROCESSOR_SERVICE_ID = 'liip_imagine.filter.post_processor.pngquant';
    private const JPEGOPTIM_POST_PROCESSOR_SERVICE_ID = 'liip_imagine.filter.post_processor.jpegoptim';
    public function process(ContainerBuilder $container): void
    {
        $pngquantBinaryPath = $this->getResolvedBinaryPath($container, ProcessorHelper::PNGQUANT);
        $jpegoptimBinaryPath =  $this->getResolvedBinaryPath($container, ProcessorHelper::JPEGOPTIM);

        $isLibrariesExists =
            $pngquantBinaryPath &&
            $jpegoptimBinaryPath &&
            $this->isLibraryExist(ProcessorHelper::PNGQUANT, $pngquantBinaryPath) &&
            $this->isLibraryExist(ProcessorHelper::JPEGOPTIM, $jpegoptimBinaryPath);

        $container->setParameter('oro_attachment.post_processors.enabled', $isLibrariesExists);

        $this->processPostProcessors($container, $isLibrariesExists, $pngquantBinaryPath, $jpegoptimBinaryPath);
    }

    private function processPostProcessors(
        ContainerBuilder $container,
        bool $isLibrariesExists,
        ?string $pngquantBinaryPath = null,
        ?string $jpegoptimBinaryPath = null
    ): void {
        if ($isLibrariesExists) {
            $container->setParameter('liip_imagine.pngquant.binary', $pngquantBinaryPath);
            $container->setParameter('liip_imagine.jpegoptim.binary', $jpegoptimBinaryPath);

            $container->getDefinition(self::PNGQUANT_POST_PROCESSOR_SERVICE_ID)
                ->setArgument(
                    '$executablePath',
                    $pngquantBinaryPath
                );
            $container->getDefinition(self::JPEGOPTIM_POST_PROCESSOR_SERVICE_ID)
                ->setArgument(
                    '$executablePath',
                    $jpegoptimBinaryPath
                );
        } else {
            $tags = $container->findTaggedServiceIds('liip_imagine.filter.post_processor');

            $container->removeDefinition(self::JPEGOPTIM_POST_PROCESSOR_SERVICE_ID);
            $container->removeDefinition(self::PNGQUANT_POST_PROCESSOR_SERVICE_ID);

            $manager = $container->getDefinition('liip_imagine.filter.manager');
            $manager->removeMethodCall('addPostProcessor');

            foreach ($tags as $id => $tag) {
                if ($container->hasDefinition($id)) {
                    $manager->addMethodCall('addPostProcessor', [$tag[0]['post_processor'], new Reference($id)]);
                }
            }
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
        return $container->getParameter($parameterName) ?: ProcessorHelper::findBinary($binaryName);
    }
}
