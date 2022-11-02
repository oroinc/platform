<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Imagine\Exception\RuntimeException;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Liip\ImagineBundle\Imagine\Cache\Helper\PathHelper;
use Oro\Bundle\AttachmentBundle\Imagine\ImagineFilterService;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        } catch (NotLoadableException $exception) {
            throw new NotFoundHttpException(
                sprintf('Source image for path "%s" could not be found', $path),
                $exception
            );
        } catch (NonExistingFilterException $exception) {
            throw new NotFoundHttpException(sprintf('Requested non-existing filter "%s"', $filter), $exception);
        } catch (RuntimeException $exception) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to create image for path "%s" and filter "%s". Message was "%s"',
                    $path,
                    $filter,
                    $exception->getMessage()
                ),
                0,
                $exception
            );
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ImagineFilterService::class,
                WebpConfiguration::class,
            ]
        );
    }
}
