<?php

namespace Oro\Bundle\SyncBundle\Authentication\Origin;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Returns application url as origin.
 */
class ApplicationOriginProvider implements OriginProviderInterface
{
    public const string APPLICATION_URL_OPTION = 'oro_ui.application_url';

    /** @var ConfigManager */
    private $configManager;

    /** @var OriginExtractor */
    private $originExtractor;

    public function __construct(
        ConfigManager $configManager,
        OriginExtractor $originExtractor
    ) {
        $this->configManager = $configManager;
        $this->originExtractor = $originExtractor;
    }

    #[\Override]
    public function getOrigins(): array
    {
        $origin = $this->originExtractor->fromUrl($this->configManager->get(self::APPLICATION_URL_OPTION));

        return $origin === null ? [] : [$origin];
    }
}
