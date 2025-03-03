<?php

namespace Oro\Bundle\FormBundle\Captcha;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use Oro\Bundle\FormBundle\Form\Type\ReCaptchaType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Google ReCaptcha v3 CAPTCHA service implementation.
 */
class ReCaptchaService implements CaptchaServiceInterface
{
    public function __construct(
        private ReCaptchaClientFactory $reCaptchaClientFactory,
        private ConfigManager $configManager,
        private RequestStack $requestStack,
        private SymmetricCrypterInterface $crypter
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->getPrivateKey() && $this->getPublicKey();
    }

    public function isVerified($value): bool
    {
        if (!$value) {
            return false;
        }

        $privateKey = $this->getPrivateKey();
        $score = $this->getScoreThreshold();
        $reCaptcha = $this->reCaptchaClientFactory->create($privateKey);

        return $reCaptcha->setExpectedHostname($this->getSiteUrl())
            ->setScoreThreshold($score)
            ->verify($value, $this->requestStack->getCurrentRequest()?->getClientIp());
    }

    public function getFormType(): string
    {
        return ReCaptchaType::class;
    }

    public function getPublicKey(): ?string
    {
        return $this->configManager->get(Configuration::getConfigKey(Configuration::RECAPTCHA_PUBLIC_KEY));
    }

    private function getSiteUrl(): string
    {
        $urlParts = parse_url($this->getCurrentUrl());

        return $urlParts['host'];
    }

    protected function getCurrentUrl(): string
    {
        return $this->configManager->get('oro_ui.application_url');
    }

    private function getPrivateKey(): ?string
    {
        $encryptedPrivateKey = $this->configManager->get(
            Configuration::getConfigKey(Configuration::RECAPTCHA_PRIVATE_KEY)
        );
        if ($encryptedPrivateKey) {
            try {
                return $this->crypter->decryptData($encryptedPrivateKey);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }

    private function getScoreThreshold(): ?float
    {
        return $this->configManager->get(Configuration::getConfigKey(Configuration::RECAPTCHA_MINIMAL_ALLOWED_SCORE));
    }
}
