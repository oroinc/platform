<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Request;

abstract class AbstractAPIAdapter implements APIAdapterInterface
{
    use LoggerAwareTrait;

    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $projectId;

    /** @var Client */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->setLogger(new NullLogger());
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
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
     * Allow adapter to modify request before sending,
     * adding API key by default
     *
     * @param Request $request
     */
    protected function preprocessRequest(Request $request)
    {
        $request->getQuery()->add('key', $this->getApiKey());
    }

    /**
     * {@inheritdoc}
     */
    public function request($uri, $data = [], $method = 'GET', $options = [], $headers = [])
    {
        $request = $this->client->createRequest(
            $method,
            $uri,
            $headers,
            $data,
            $options
        );

        if (!in_array($method, ['POST', 'PUT'], true)) {
            $request->getQuery()->merge($data);
        }

        $this->preprocessRequest($request);
        try {
            $response = $request->send();
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    /**
     * Extract list of folders recursively from file paths
     *
     * @param array $files
     *
     * @return array
     */
    protected function getFileFolders(array $files)
    {
        $dirs = [];

        foreach ($files as $remotePath) {
            $subFolders = explode(DIRECTORY_SEPARATOR, dirname($remotePath));

            $currentDir = [];
            foreach ($subFolders as $folderName) {
                $currentDir[] = $folderName;

                // crowdin understand only "/" as directory separator
                $path         = implode('/', $currentDir);
                $dirs[]  = $path;
            }
        }

        return $dirs;
    }
}
