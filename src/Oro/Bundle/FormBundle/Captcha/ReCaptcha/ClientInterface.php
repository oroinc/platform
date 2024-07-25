<?php

namespace Oro\Bundle\FormBundle\Captcha\ReCaptcha;

/**
 * Google reCAPTCHA v3 client interface.
 */
interface ClientInterface
{
    public function setExpectedHostname(string $hostname): self;

    public function setScoreThreshold(float $threshold): self;

    public function verify(string $response, $remoteIp = null): bool;
}
