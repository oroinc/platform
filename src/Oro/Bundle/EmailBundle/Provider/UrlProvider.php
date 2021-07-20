<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Base class to provide url based on configuration settings (application url) as well as
 * generated value by UrlGenerator
 */
class UrlProvider
{
    use UrlProviderTrait;

    const APPLICATION_URL = 'oro_ui.application_url';

    /** @var ConfigManager */
    protected $configManager;

    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

    public function __construct(ConfigManager $configManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->configManager = $configManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Returns absolute url based on application url setting with generated route
     */
    public function getAbsoluteUrl($route, array $routeParams = []): string
    {
        $applicationUrl = $this->configManager->get(self::APPLICATION_URL);

        return $this->preparePath($applicationUrl, $route, $routeParams);
    }
}
