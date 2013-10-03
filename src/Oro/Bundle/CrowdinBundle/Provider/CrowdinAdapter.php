<?php

namespace Oro\Bundle\CrowdinBundle\Provider;

class CrowdinAdapter
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

    public function __construct($apiKey, $endpoint)
    {
        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
        $this->projectId = 'test-bap';
    }

    public function addFile($file)
    {
        try {
            $result = $this->request(
                '/project/'.$this->projectId.'/add-file',
                array(
                    'files[test.en.yml]' => $file,
                )
            );
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

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
