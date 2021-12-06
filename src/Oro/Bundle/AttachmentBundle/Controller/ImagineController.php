<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Imagine\Exception\RuntimeException;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Liip\ImagineBundle\Imagine\Cache\Helper\PathHelper;
use Oro\Bundle\AttachmentBundle\Imagine\ImagineFilterService;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves requests to filter local images.
 * Converts image to webp format if the requested file has '.webp' extension webp strategy is not disabled.
 */
class ImagineController extends AbstractController
{
    public function filterAction(string $path, string $filter): Response
    {
        $path = PathHelper::urlPathToFilePath($path);

        try {
            if ($this->isWebp($path)) {
                $format = 'webp';
                $path = $this->stripWebpFromPath($path);
            }

            $url = $this->getImagineFilterService()->getUrlOfFilteredImage($path, $filter, $format ?? '');
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

    private function isWebp(string $path): bool
    {
        return pathinfo($path, PATHINFO_EXTENSION) === 'webp' &&
            !$this->get(WebpConfiguration::class)->isDisabled();
    }

    private function stripWebpFromPath($path): string
    {
        $pathWithoutWebp = mb_substr($path, 0, -5);
        if (!pathinfo($pathWithoutWebp, PATHINFO_EXTENSION)) {
            // Returns original path as '.webp' is the only extension present.
            return $path;
        }

        return $pathWithoutWebp;
    }

    private function getImagineFilterService(): ImagineFilterService
    {
        return $this->container->get('oro_attachment.imagine.filter_service');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                'oro_attachment.imagine.filter_service' => ImagineFilterService::class,
                WebpConfiguration::class,
            ]
        );
    }
}
