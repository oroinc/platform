<?php

namespace Oro\Bundle\AttachmentBundle\Configurator\Provider;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

/**
 * Provides runtime configuration for preserving original image metadata
 * during image processing using Metadata Service post-processor.
 */
class RuntimeMetadataConfigurationProvider implements RuntimeConfigProviderInterface, FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(private ConfigManager $configManager)
    {
    }

    public function isSupported(string $filter): bool
    {
        return $this->isFeaturesEnabled() && $this->configManager->get('oro_attachment.metadata_service_allowed');
    }

    public function getRuntimeConfig(string $filter, RuntimeContext $context): array
    {
        if ($context->offsetExists('original_content')) {
            $originalContent = $context->offsetGet('original_content');
            $fileName = $context->offsetExists('file_name') ? $context->offsetGet('file_name') : null;

            if ($originalContent instanceof BinaryInterface) {
                return $this->getConfig($originalContent, $fileName);
            }
        }

        // Check 'metadata_refresh_hash' context key to maintain backward compatibility
        // with existing code that triggers metadata refresh without providing original content
        if ($context->offsetExists('metadata_refresh_hash')) {
            return $this->getConfig();
        }

        return [];
    }

    private function getConfig(?BinaryInterface $content = null, ?string $fileName = null): array
    {
        return [
            'post_processors' => [
                'oro_metadata_service' => [
                    'original_content' => $content?->getContent(),
                    'file_name' => $fileName
                ]
            ]
        ];
    }
}
