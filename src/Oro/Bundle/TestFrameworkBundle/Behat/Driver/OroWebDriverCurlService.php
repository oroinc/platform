<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Driver;

use WebDriver\Service\CurlService as WebDriverCurlService;
use WebDriver\Service\CurlServiceInterface;

class OroWebDriverCurlService implements CurlServiceInterface
{
    const LOG_FILE = 'behat_curl.log';

    /**
     * @var WebDriverCurlService
     */
    protected $webDriverCurl;

    protected $extraOptions = [
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_MAXREDIRS => 10,
    ];

    public function __construct()
    {
        $this->webDriverCurl = new WebDriverCurlService();
    }

    /**
     * {@inheritdoc}
     */
    public function execute($requestMethod, $url, $parameters = null, $extraOptions = [])
    {
        return $this->webDriverCurl
            ->execute($requestMethod, $url, $parameters, array_replace($this->extraOptions, $extraOptions));
    }

    /**
     * @param string $logDir path to kernel log dir
     */
    public function setLogDir($logDir)
    {
        $logFile = $logDir.DIRECTORY_SEPARATOR.self::LOG_FILE;
        if (is_file($logFile)) {
            unlink($logFile);
        }

        $logFileResource = fopen($logFile, 'a+b');
        $this->extraOptions[CURLOPT_VERBOSE] = true;
        $this->extraOptions[CURLOPT_STDERR] = $logFileResource;
    }
}
