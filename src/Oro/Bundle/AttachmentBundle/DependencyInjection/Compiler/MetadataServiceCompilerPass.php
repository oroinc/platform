<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures Metadata Service for image metadata preservation.
 */
class MetadataServiceCompilerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $serviceUrl = $this->getResolvedBinaryPath($container, 'oro_attachment.metadata_service_url');
        $apiKey = $this->getResolvedBinaryPath($container, 'oro_attachment.metadata_service_api_key');

        $isMetadataServiceConfigured = !empty($serviceUrl) && !empty($apiKey);
        $container->setParameter('oro_attachment.metadata_service.enabled', $isMetadataServiceConfigured);
    }

    private function getResolvedBinaryPath(ContainerBuilder $container, string $parameterName): mixed
    {
        return $container->resolveEnvPlaceholders($container->getParameter($parameterName), true);
    }
}
