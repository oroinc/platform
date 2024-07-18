<?php

namespace Oro\Bundle\FormBundle\Captcha;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\DependencyInjection\Configuration;

/**
 * Registry that holds CAPTCHA services.
 */
class CaptchaServiceRegistry
{
    private iterable $captchaServices;

    public function __construct(
        private ConfigManager $configManager,
        iterable $captchaServices
    ) {
        $this->captchaServices = $captchaServices instanceof \Traversable
            ? iterator_to_array($captchaServices)
            : $captchaServices;
    }

    public function getCaptchaServiceAliases(): array
    {
        return array_keys($this->captchaServices);
    }

    public function getCaptchaService(): CaptchaServiceInterface
    {
        $selectedService = $this->configManager->get(Configuration::getConfigKey(Configuration::CAPTCHA_SERVICE));
        $service = $this->captchaServices[$selectedService] ?? null;

        if (!$service) {
            throw new \InvalidArgumentException(sprintf('Captcha service "%s" not found', $selectedService));
        }

        return $service;
    }
}
