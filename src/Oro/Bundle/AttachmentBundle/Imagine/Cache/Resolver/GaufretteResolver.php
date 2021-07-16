<?php

namespace Oro\Bundle\AttachmentBundle\Imagine\Cache\Resolver;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Component\Routing\RequestContext;

/**
 * The liip imagine cache resolver that enables cache resolution using Gaufrette filesystem abstraction layer.
 */
class GaufretteResolver implements ResolverInterface
{
    /** @var FileManager */
    private $fileManager;

    /** @var RequestContext */
    private $requestContext;

    /** @var string */
    private $urlPrefix;

    /** @var string */
    private $cachePrefix;

    public function __construct(
        FileManager $fileManager,
        RequestContext $requestContext,
        string $urlPrefix = 'media/cache',
        string $cachePrefix = ''
    ) {
        $this->fileManager = $fileManager;
        $this->requestContext = $requestContext;
        $this->urlPrefix = rtrim(str_replace('//', '/', $urlPrefix), '/');
        $this->cachePrefix = ltrim(str_replace('//', '/', $cachePrefix), '/');
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($path, $filter)
    {
        return $this->getBaseUrl() . '/' . $this->getFileUrl($path, $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function isStored($path, $filter)
    {
        return $this->fileManager->hasFile($this->getFilePath($path, $filter));
    }

    /**
     * {@inheritdoc}
     */
    public function store(BinaryInterface $binary, $path, $filter)
    {
        $this->fileManager->writeToStorage(
            $binary->getContent(),
            $this->getFilePath($path, $filter)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function remove(array $paths, array $filters)
    {
        if (empty($filters)) {
            return;
        }

        if (empty($paths)) {
            foreach ($filters as $filter) {
                $path = $filter . '/';
                if ($this->cachePrefix) {
                    $path = $this->cachePrefix . '/' . $path;
                }
                $this->fileManager->deleteAllFiles($path);
            }
        } else {
            foreach ($paths as $path) {
                foreach ($filters as $filter) {
                    $this->fileManager->deleteFile($this->getFilePath($path, $filter));
                }
            }
        }
    }

    private function getFilePath(string $path, string $filter): string
    {
        $path = $filter . '/' . $this->sanitizePath($path);
        if ($this->cachePrefix) {
            $path = $this->cachePrefix . '/' . $path;
        }

        return $path;
    }

    private function getFileUrl(string $path, string $filter): string
    {
        return $this->urlPrefix . '/' . $filter . '/' . $this->sanitizePath($path);
    }

    private function getBaseUrl(): string
    {
        $scheme = $this->requestContext->getScheme();

        $port = '';
        if ('https' === $scheme && 443 !== $this->requestContext->getHttpsPort()) {
            $port = ':' . $this->requestContext->getHttpsPort();
        } elseif ('http' === $scheme && 80 !== $this->requestContext->getHttpPort()) {
            $port = ':' . $this->requestContext->getHttpPort();
        }

        $baseUrl = $this->requestContext->getBaseUrl();
        if ('.php' === mb_substr($baseUrl, -4)) {
            $baseUrl = pathinfo($baseUrl, PATHINFO_DIRNAME);
        }
        $baseUrl = rtrim($baseUrl, '/\\');

        return
            $scheme
            . '://'
            . $this->requestContext->getHost()
            . $port
            . $baseUrl;
    }

    private function sanitizePath(string $path): string
    {
        // crude way of sanitizing URL scheme ("protocol") part
        $path = str_replace('://', '---', $path);

        return ltrim($path, '/');
    }
}
