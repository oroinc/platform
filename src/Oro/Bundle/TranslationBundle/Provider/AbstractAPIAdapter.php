<?php

namespace Oro\Bundle\TranslationBundle\Provider;

abstract class AbstractAPIAdapter
{
    /**
     * @var string
     */
    protected $apiKey;

    /** @var  \Closure|null */
    protected $progressCallback = null;

    /**
     * @var string endpoint URL
     */
    protected $endpoint;

    public function __construct($endpoint)
    {
        $this->endpoint  = $endpoint;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Upload source files to translation service
     *
     * @param string $files file list with translations
     * @param string $mode 'update' or 'add'
     *
     * @return mixed
     */
    abstract public function upload($files, $mode = 'add');

    /**
     * Download translations
     *
     * @param string $path save downloaded file to this path
     * @param string $package
     *
     * @return mixed
     */
    abstract public function download($path, $package = 'all');

    /**
     * Perform request
     *
     * @param        $uri
     * @param array  $data
     * @param string $method
     * @param array  $curlOptions
     *
     * @throws \Exception
     * @return \SimpleXMLElement
     */
    protected function request($uri, $data = array(), $method = 'GET', $curlOptions = [])
    {
        $requestParams = [
                CURLOPT_URL            => $this->endpoint . $uri . '?key=' . $this->apiKey,
                CURLOPT_RETURNTRANSFER => true,
            ] + $curlOptions;

        if ($method == 'POST') {
            $requestParams[CURLOPT_POST] = true;
            $requestParams[CURLOPT_POSTFIELDS] = $data;
        }

        $ch = curl_init();
        curl_setopt_array(
            $ch,
            $requestParams
        );

        $result = curl_exec($ch);
        if (!$result) {
            $errorCode = curl_errno($ch);
            $error = curl_error($ch);
            throw new \Exception($error, $errorCode);
        }
        curl_close($ch);

        return $result;
    }

    /**
     * Notify progress status
     */
    public function notifyProgress()
    {
        if (is_callable($this->progressCallback)) {
            call_user_func($this->progressCallback, func_get_args());
        }

        return $this;
    }

    /**
     * @param \Closure $progressCallback
     */
    public function setProgressCallback(\Closure $progressCallback)
    {
        $this->progressCallback = $progressCallback;
    }
}
