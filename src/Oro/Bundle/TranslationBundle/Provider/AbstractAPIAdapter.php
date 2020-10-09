<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Utils;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Abstract translation API adapter that simplifies sending REST requests
 */
abstract class AbstractAPIAdapter implements APIAdapterInterface
{
    use LoggerAwareTrait;

    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $projectId;

    /** @var ClientInterface */
    protected $client;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
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
     * Allow adapter to replace the request before sending,
     * adding API key by default
     *
     * @param Request $request
     * @return Request
     */
    protected function replaceRequest(Request $request): Request
    {
        $uriWithApiKey = Uri::withQueryValue($request->getUri(), 'key', $this->getApiKey());

        return $request->withUri($uriWithApiKey);
    }

    /**
     * {@inheritdoc}
     */
    public function request($uri, $data = [], $method = 'GET', $options = [], $headers = []): ResponseInterface
    {
        $request = new Request(
            $method,
            $uri,
            $headers,
            Utils::jsonEncode($data)
        );

        if (!in_array($method, ['POST', 'PUT'], true)) {
            $uri = Uri::withQueryValues($request->getUri(), $data);
            $request = $request->withUri($uri);
        }

        $request = $this->replaceRequest($request);
        try {
            $response = $this->client->send($request, $options);
        } catch (ClientExceptionInterface $e) {
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
            $remotePath = str_replace(DIRECTORY_SEPARATOR, '/', dirname($remotePath));
            $subFolders = array_filter(explode('/', $remotePath));

            $currentDir = [];
            foreach ($subFolders as $folderName) {
                $currentDir[] = $folderName;

                // crowdin understand only "/" as directory separator
                $path = implode('/', $currentDir);
                $dirs[] = $path;
            }
        }

        return array_unique($dirs);
    }

    /**
     * @param ResponseInterface $response
     * @return array|bool|float|int|object|string|null
     */
    protected function jsonDecode(ResponseInterface $response)
    {
        return Utils::jsonDecode($response->getBody()->getContents(), true);
    }
}
