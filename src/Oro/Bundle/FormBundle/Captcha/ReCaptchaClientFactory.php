<?php

namespace Oro\Bundle\FormBundle\Captcha;

use GuzzleHttp\ClientInterface as HTTPClientInterface;
use Oro\Bundle\FormBundle\Captcha\ReCaptcha\Client;
use Oro\Bundle\FormBundle\Captcha\ReCaptcha\ClientInterface;
use Oro\Bundle\FormBundle\Captcha\ReCaptcha\GoogleClientAdapter;
use Psr\Log\LoggerInterface;

/**
 * Factory to create recaptcha client instance.
 */
class ReCaptchaClientFactory
{
    public function __construct(
        private HTTPClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function create(string $privateKey): ClientInterface
    {
        // When Google reCAPTCHA PHP library is installed prefer it over simplified client.
        if (\class_exists('\ReCaptcha\ReCaptcha')) {
            return new GoogleClientAdapter(new \ReCaptcha\ReCaptcha($privateKey));
        }

        return new Client($this->httpClient, $this->logger, $privateKey);
    }
}
