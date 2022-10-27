<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Imagine\Exception\RuntimeException;
use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The controller with actions that work with files.
 */
class FileController extends AbstractController
{
    public function getFileAction(int $id, string $filename, string $action, Request $request): Response
    {
        $file = $this->getFileById($id);
        $this->unlockSession($request);
        $this->assertValidFilename($file, $filename);

        $response = new Response();
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

        if (FileUrlProviderInterface::FILE_ACTION_GET === $action) {
            $response->headers->set('Content-Type', $file->getMimeType() ?: 'application/force-download');
        } else {
            $response->headers->set('Content-Type', 'application/force-download');

            $contentDisposition = 'attachment';
            if ($file->getOriginalFilename()) {
                $contentDisposition .= sprintf(';filename="%s"', addslashes($file->getOriginalFilename()));
            }
            $response->headers->set('Content-Disposition', $contentDisposition);
        }

        $response->headers->set('Content-Length', $file->getFileSize());
        $response->setContent($this->getFileManager()->getContent($file));

        return $response;
    }

    public function getResizedAttachmentImageAction(
        int $id,
        int $width,
        int $height,
        string $filename,
        Request $request
    ): Response {
        $file = $this->getFileById($id);
        $this->unlockSession($request);

        try {
            $this->assertValidResizedImageName($file, $filename, $width, $height);

            // Image name is assumed to be valid at this point, so we can pick its extension.
            $format = pathinfo($filename, PATHINFO_EXTENSION);

            $binary = $this->getImageResizeManager()->resize($file, $width, $height, $format);
        } catch (RuntimeException $exception) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to create image "%s" resized to width "%d" and height "%d". Message was "%s"',
                    $filename,
                    $width,
                    $height,
                    $exception->getMessage()
                ),
                0,
                $exception
            );
        }

        if (!isset($binary)) {
            throw $this->createNotFoundException();
        }

        return new Response($binary->getContent(), Response::HTTP_OK, ['Content-Type' => $binary->getMimeType()]);
    }

    public function getFilteredImageAction(int $id, string $filter, string $filename, Request $request): Response
    {
        $file = $this->getFileById($id);
        $this->unlockSession($request);

        try {
            $this->assertValidFilteredImageName($file, $filename, $filter);

            // Image name is assumed to be valid at this point, so we can pick its extension.
            $format = pathinfo($filename, PATHINFO_EXTENSION);

            $binary = $this->getImageResizeManager()->applyFilter($file, $filter, $format);
        } catch (NonExistingFilterException $exception) {
            throw new NotFoundHttpException(sprintf('Requested non-existing filter "%s"', $filter), $exception);
        } catch (RuntimeException $exception) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to create image "%s" using filter "%s". Message was "%s"',
                    $filename,
                    $filter,
                    $exception->getMessage()
                ),
                0,
                $exception
            );
        }

        if (!isset($binary)) {
            throw $this->createNotFoundException();
        }

        return new Response($binary->getContent(), Response::HTTP_OK, ['Content-Type' => $binary->getMimeType()]);
    }

    private function getFileById(int $id): File
    {
        /** @var File|null $file */
        $file = $this->container->get('doctrine')->getManagerForClass(File::class)->find(File::class, $id);
        if (!$file) {
            throw $this->createNotFoundException('File not found');
        }

        if (!$this->isGranted('VIEW', $file)) {
            throw $this->createAccessDeniedException();
        }

        return $file;
    }

    private function assertValidFilename(File $file, string $filename): void
    {
        if ($filename === $file->getFilename()
            || $filename === $file->getOriginalFilename()
            || $filename === $this->getFileNameProvider()->getFileName($file)) {
            return;
        }

        throw $this->createNotFoundException('File not found');
    }

    private function assertValidFilteredImageName(File $file, string $filename, string $filterName): void
    {
        if ($filename === $this->getFileNameProvider()->getFilteredImageName($file, $filterName)) {
            return;
        }

        if ($this->getWebpConfiguration()->isEnabledIfSupported()
            && $filename === $this->getFileNameProvider()->getFilteredImageName($file, $filterName, 'webp')) {
            return;
        }

        throw $this->createNotFoundException('File not found');
    }

    private function assertValidResizedImageName(File $file, string $filename, int $width, int $height): void
    {
        if ($filename === $this->getFileNameProvider()->getResizedImageName($file, $width, $height)) {
            return;
        }

        if ($this->getWebpConfiguration()->isEnabledIfSupported()
            && $filename === $this->getFileNameProvider()->getResizedImageName($file, $width, $height, 'webp')) {
            return;
        }

        throw $this->createNotFoundException('File not found');
    }

    private function unlockSession(Request $request): void
    {
        $session = $request->hasSession() ? $request->getSession() : null;
        if ($session && $session->isStarted()) {
            $session->save();
        }
    }

    private function getFileManager(): FileManager
    {
        return $this->container->get(FileManager::class);
    }

    private function getImageResizeManager(): ImageResizeManagerInterface
    {
        return $this->container->get(ImageResizeManagerInterface::class);
    }

    private function getFileNameProvider(): FileNameProviderInterface
    {
        return $this->container->get(FileNameProviderInterface::class);
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
        return array_merge(parent::getSubscribedServices(), [
            AttachmentManager::class,
            FileManager::class,
            ImageResizeManagerInterface::class,
            FileNameProviderInterface::class,
            WebpConfiguration::class,
        ]);
    }
}
