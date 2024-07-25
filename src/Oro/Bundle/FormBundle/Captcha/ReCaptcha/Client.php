<?php

namespace Oro\Bundle\FormBundle\Captcha\ReCaptcha;

use GuzzleHttp\ClientInterface as HTTPClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Simple Google reCAPTCHA v3 client implementation.
 * When possible, prefer the google/recaptcha instead.
 */
class Client implements ClientInterface
{
    private const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    private const VERSION = 'php_1.3.0';

    private ?string $expectedHostname = null;
    private ?float $scoreThreshold = null;

    public function __construct(
        private HTTPClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $secret
    ) {
    }

    public function setExpectedHostname(string $hostname): self
    {
        $this->expectedHostname = $hostname;

        return $this;
    }

    public function setScoreThreshold(float $threshold): self
    {
        $this->scoreThreshold = $threshold;

        return $this;
    }

    public function verify(string $response, $remoteIp = null): bool
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                static::SITE_VERIFY_URL,
                [
                    'form_params' => [
                        'secret' => $this->secret,
                        'response' => $response,
                        'remoteip' => $remoteIp,
                        'version' => self::VERSION
                    ]
                ]
            );
            $responseData = json_decode($response->getBody()->getContents(), JSON_OBJECT_AS_ARRAY);
            $responseHostname = $responseData['hostname'] ?? '';
            $responseScore = $responseData['score'] ?? null;

            if ($this->expectedHostname !== null && strcasecmp($this->expectedHostname, $responseHostname) !== 0) {
                $this->logger->debug(
                    'reCAPTCHA Hostname mismatch',
                    ['response' => $responseData]
                );

                return false;
            }

            if ($this->scoreThreshold !== null && $this->scoreThreshold > $responseScore) {
                $this->logger->debug(
                    'reCAPTCHA score not met',
                    ['response' => $responseData]
                );

                return false;
            }

            return (bool)($responseData['success'] ?? false);
        } catch (\Exception $e) {
            $this->logger->warning(
                'Unable to verify reCAPTCHA',
                ['exception' => $e]
            );

            return false;
        }
    }
}
