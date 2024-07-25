<?php

namespace Oro\Bundle\FormBundle\Captcha\ReCaptcha;

use ReCaptcha\ReCaptcha;

/**
 * Google reCAPTCHA v3 client adapter.
 */
class GoogleClientAdapter implements ClientInterface
{
    public function __construct(
        private ReCaptcha $client
    ) {
    }

    public function setExpectedHostname(string $hostname): ClientInterface
    {
        $this->client->setExpectedHostname($hostname);

        return $this;
    }

    public function setScoreThreshold(float $threshold): ClientInterface
    {
        $this->client->setScoreThreshold($threshold);

        return $this;
    }

    public function verify(string $response, $remoteIp = null): bool
    {
        return $this->client->verify($response, $remoteIp)->isSuccess();
    }
}
