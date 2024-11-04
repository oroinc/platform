<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Imagine\Cache\Helper\PathHelper;
use Oro\Bundle\AttachmentBundle\Imagine\ImagineFilterService;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves requests to filter local images.
 * Can return url to the image in WebP format if webp strategy is not disabled.
 */
class ImagineController extends AbstractController
{
    public function getFilteredImageAction(string $path, string $filter): Response
    {
        $path = PathHelper::urlPathToFilePath($path);

        try {
            $url = $this->getUrlOfFilteredImage($path, $filter);
        } catch (\Exception $exception) {
            $this->getLogger()->error(
                'Unable to get filtered image',
                [
                    'exception' => $exception,
                    'filter' => $filter,
                    'path' => $path
                ]
            );

            throw $this->createNotFoundException('Not Found', $exception);
        }

        return new RedirectResponse($url);
    }

    private function getUrlOfFilteredImage(
        string $path,
        string $filterName,
        string $format = ''
    ): string {
        try {
            $url = $this->getImagineFilterService()->getUrlOfFilteredImage($path, $filterName, $format);
        } catch (NotLoadableException $exception) {
            $pathinfo = pathinfo($path) + ['dirname' => '', 'filename' => '', 'extension' => ''];
            $requestedExtension = $pathinfo['extension'];
            if (!$requestedExtension) {
                throw $exception;
            }

            if (!$this->isAllowedExtension($requestedExtension)) {
                $requestedExtension = '';
            }

            $pathWithoutExtension = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'];
            $url = $this->getUrlOfFilteredImage($pathWithoutExtension, $filterName, $requestedExtension);
        }

        return $url;
    }

    private function isAllowedExtension(string $extension): bool
    {
        return FilenameExtensionHelper::canonicalizeExtension($extension) === 'webp'
            && !$this->getWebpConfiguration()->isDisabled();
    }

    private function getImagineFilterService(): ImagineFilterService
    {
        return $this->container->get(ImagineFilterService::class);
    }

    private function getWebpConfiguration(): WebpConfiguration
    {
        return $this->container->get(WebpConfiguration::class);
    }

    private function getLogger(): LoggerInterface
    {
        return $this->container->get(LoggerInterface::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ImagineFilterService::class,
                WebpConfiguration::class,
                LoggerInterface::class
            ]
        );
    }
}
