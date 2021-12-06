<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Configurator;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;

trait AttachmentSettingsTrait
{
    use ConfigManagerAwareTestTrait;

    protected function changeProcessorsParameters(
        int $jpegQuality = 85,
        int $pngQuality = 100,
        bool $processorsAllowed = true
    ): void {
        $configManager = self::getConfigManager();
        $configManager->set('oro_attachment.jpeg_quality', $jpegQuality);
        $configManager->set('oro_attachment.png_quality', $pngQuality);
        $configManager->set('oro_attachment.processors_allowed', $processorsAllowed);
        $configManager->flush();
    }
}
