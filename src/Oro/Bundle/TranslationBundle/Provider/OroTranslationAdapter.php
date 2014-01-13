<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use FOS\Rest\Util\Codes;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

class OroTranslationAdapter implements APIAdapterInterface
{
    const URL_STATS    = '/stats';
    const URL_DOWNLOAD = '/download';

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
     * {@inheritdoc}
     */
    public function download($path, array $projects, $package = null)
    {
        $package = is_null($package) ? 'all' : str_replace('_', '-', $package);

        $fileHandler = fopen($path, 'wb');
        $result      = $this->request(
            self::URL_DOWNLOAD,
            [
                'packages' => implode(',', $projects),
                'lang'     => $package,
            ],
            'GET',
            [
                CURLOPT_FILE           => $fileHandler,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_HEADER         => false,
            ]
        );

        fclose($fileHandler);

        return $result;
    }

    /**
     * Fetch statistic
     *
     * @param array $packages
     *
     * @throws \RuntimeException
     * @return array [
     *     ['code' => 'en', 'translationStatus' => 30, 'lastBuildDate' => \DateTime::ISO8601 - string ]
     * ]
     */
    public function fetchStatistic(array $packages = [])
    {
        $response = $this->request(
            self::URL_STATS,
            ['packages' => implode(',', $packages)]
        );

        $responseCode = $this->apiRequest->getResponseCode();
        if ($responseCode === Codes::HTTP_OK) {
            $result = json_decode($response, true);
            $result = is_array($result) ? $result : [];

            $filtered = array_filter(
                $result,
                function ($item) {
                    return isset($item['code']) && isset($item['translationStatus']) && isset($item['lastBuildDate']);
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
     * {@inheritdoc}
     */
    public function request($uri, $data = array(), $method = 'GET', $curlOptions = [])
    {
        $data['key'] = $this->apiKey;
        $data        = '?' . http_build_query($data, '', '&');

        $requestParams = [
                CURLOPT_URL            => $this->endpoint . $uri . $data,
                CURLOPT_RETURNTRANSFER => true,
            ] + $curlOptions;
        $this->apiRequest->setOptions($requestParams);

        return $this->apiRequest->execute();
    }

    /**
     * @param $response
     *
     * @return \stdClass
     * @throws \RuntimeException
     */
    public function parseResponse($response)
    {
        $result = json_decode($response);
        if (isset($result->message)) {
            $code = isset($result->code) ? (int)$result->code : 0;
            throw new \RuntimeException($result->message, $code);
        }

        return $result;
    }
}
