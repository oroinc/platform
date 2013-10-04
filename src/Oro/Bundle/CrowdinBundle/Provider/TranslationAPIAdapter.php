<?php

namespace Oro\Bundle\CrowdinBundle\Provider;


abstract class TranslationAPIAdapter
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string endpoint URL
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $projectId;

    public function __construct($projectId, $apiKey, $endpoint)
    {
        $this->projectId = $projectId;
        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
    }

    /**
     * Upload source files to translation service
     *
     * @param string $dir directory or file to upload
     * @return mixed
     */
    abstract public function upload($dir);

    /**
     * Perform request
     *
     * @param $uri
     * @param array $data
     * @param string $method
     * @throws \Exception
     *
     * @return boolean
     */
    protected function request($uri, $data = array(), $method = 'GET')
    {
        $ch = curl_init();
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL => $this->endpoint . $uri . '?key=' . $this->apiKey,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
            )
        );

        $result = curl_exec($ch);
        if (!$result) {
            $error = curl_errno($ch);
            throw new \Exception($error);
        }
        curl_close($ch);

        return $result;
    }
}
