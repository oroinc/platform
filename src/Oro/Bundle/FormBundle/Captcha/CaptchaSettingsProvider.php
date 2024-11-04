<?php

namespace Oro\Bundle\FormBundle\Captcha;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\DependencyInjection\Configuration;

/**
 * Provides settings required to use CAPTCHA service.
 */
class CaptchaSettingsProvider implements CaptchaSettingsProviderInterface
{
    public function __construct(
        private ConfigManager $configManager,
        private CaptchaServiceRegistry $captchaServiceRegistry
    ) {
    }

    #[\Override]
    public function isProtectionAvailable(): bool
    {
        if (!$this->configManager->get(Configuration::getConfigKey(Configuration::ENABLED_CAPTCHA))) {
            return false;
        }

        return $this->getCaptchaService()->isConfigured();
    }

    #[\Override]
    public function isFormProtected(string $formName): bool
    {
        $protectedForms = $this->configManager->get(
            Configuration::getConfigKey(Configuration::CAPTCHA_PROTECTED_FORMS)
        );
        if (\in_array($formName, $protectedForms, true)) {
            return true;
        }

        return false;
    }

    #[\Override]
    public function getFormType(): string
    {
        return $this->getCaptchaService()->getFormType();
    }

    private function getCaptchaService(): CaptchaServiceInterface
    {
        return $this->captchaServiceRegistry->getCaptchaService();
    }
}
