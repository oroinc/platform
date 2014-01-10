<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use FOS\Rest\Util\Codes;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

class OroTranslationAdapter
{
    const URL_STATISTIC = '/statistic';

    /** @var string */
    protected $apiKey;

    /** @var string endpoint URL */
    protected $endpoint;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ApiRequestInterface */
    protected $apiRequest;

    public function __construct(ApiRequestInterface $apiRequest, $endpoint, $apiKey = null)
    {
        $this->apiRequest = $apiRequest;
        $this->setEndpoint($endpoint);
        $this->setApiKey($apiKey);
        $this->setLogger(new NullLogger());
    }

    /**
     * Download translations
     *
     * @param string $path save downloaded file to this path
     * @param string $package
     *
     * @return mixed
     */
    public function download($path, $package = null)
    {
        // TODO: Implement download() method.
    }

    /**
     * Fetch statistic
     *
     * @param array $packages
     *
     * @throws \RuntimeException
     * @return array [
     *     ['code' => 'en', 'translationStatus' => 30]
     * ]
     */
    public function fetchStatistic(array $packages = [])
    {
        $response = $this->request(
            self::URL_STATISTIC,
            ['packages' => implode(',', $packages)]
        );

        $responseCode = $this->apiRequest->getResponseCode();
        if ($responseCode === Codes::HTTP_OK) {
            $result = json_decode($response, true);
            $result = is_array($result) ? $result : [];

            $filtered = array_filter(
                $result,
                function ($item) {
                    return isset($item['code']) && isset($item['translationStatus']);
                }
            );

            if (empty($filtered) || empty($result)) {
                $this->logger->critical('Bad data received' . PHP_EOL . var_export($result, true));
                throw new \RuntimeException('Bad data received');
            }

            return $result;
        } else {
            $this->logger->critical('Service unavailable. Status received: ' . $responseCode);
            throw new \RuntimeException('Service unavailable');
        }
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Sets a logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Perform request
     *
     * @param string $uri
     * @param array  $urlParams
     * @param array  $curlOptions
     *
     * @return mixed
     */
    protected function request($uri, $urlParams = [], $curlOptions = [])
    {
        $urlParams['key'] = $this->apiKey;
        $urlParams        = '?' . http_build_query($urlParams, '', '&');

        $requestParams = [
                CURLOPT_URL            => $this->endpoint . $uri . $urlParams,
                CURLOPT_RETURNTRANSFER => true,
            ] + $curlOptions;
        $this->apiRequest->setOptions($requestParams);

        return $this->apiRequest->execute();
    }
}
