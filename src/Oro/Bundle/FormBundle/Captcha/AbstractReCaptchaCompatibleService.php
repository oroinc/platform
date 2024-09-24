<?php

namespace Oro\Bundle\FormBundle\Captcha;

use GuzzleHttp\ClientInterface as HTTPClientInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Abstract implementation for ReCaptcha v2 compatible services.
 */
abstract class AbstractReCaptchaCompatibleService implements CaptchaServiceInterface
{
    public function __construct(
        protected HTTPClientInterface $httpClient,
        protected LoggerInterface $logger,
        protected ConfigManager $configManager,
        protected SymmetricCrypterInterface $crypter,
        protected RequestStack $requestStack
    ) {
    }

    abstract protected function getPublicKeyConfigKey(): string;

    abstract protected function getPrivateKeyConfigKey(): string;

    abstract protected function getSurveyUrl(): string;

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->getPrivateKey() && $this->getPublicKey();
    }

    #[\Override]
    public function isVerified($value): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        try {
            $response = $this->httpClient->request(
                'POST',
                $this->getSurveyUrl(),
                [
                    'form_params' => [
                        'secret' => $this->getPrivateKey(),
                        'response' => $value,
                        'remoteip' => $request?->getClientIp()
                    ]
                ]
            );
            $responseData = json_decode($response->getBody()->getContents(), JSON_OBJECT_AS_ARRAY);

            return (bool)($responseData['success'] ?? false);
        } catch (\Exception $e) {
            $this->logger->warning(
                'Unable to verify CAPTCHA',
                ['exception' => $e]
            );

            return false;
        }
    }

    #[\Override]
    public function getPublicKey(): ?string
    {
        return $this->configManager->get($this->getPublicKeyConfigKey());
    }

    protected function getPrivateKey(): ?string
    {
        $encryptedPrivateKey = $this->configManager->get($this->getPrivateKeyConfigKey());
        if ($encryptedPrivateKey) {
            try {
                return $this->crypter->decryptData($encryptedPrivateKey);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }
}
