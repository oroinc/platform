<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Imagine\Exception\RuntimeException;
use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The controller with actions that work with files.
 */
class FileController extends AbstractController
{
    public const int CACHE_CONTROL_DEFAULT_MAX_AGE = 3600;

    private const string CONTROL_CHARACTERS_PATTERN = '/[\x00-\x1F\x7F]/';

    public function getFileAction(int $id, string $filename, string $action, Request $request): Response
    {
        $file = $this->getFileById($id);
        $this->unlockSession($request);
        $this->assertValidFilename($file, $filename);

        $mimeType = MimeTypesConverter::convertToMediaType((string)$file->getMimeType());
        $isInline = FileUrlProviderInterface::FILE_ACTION_GET === $action
            && \in_array($mimeType, $this->getParameter('oro_attachment.inline_mime_types'), true);

        $response = new Response($this->getFileManager()->getContent($file));
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Content-Type', $this->getContentType($action, $mimeType));
        $response->headers->set('Content-Length', $file->getFileSize());

        if (!$isInline) {
            $response->headers->set('Content-Disposition', $this->getAttachmentContentDisposition($file));
            $response->headers->set('Content-Security-Policy', 'sandbox');
        }

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
        $response = new Response();
        $sessionConfig = $this->getParameter('session.storage.options');
        $imageMaxLifetime = \array_key_exists('cookie_lifetime', $sessionConfig)
            ? $sessionConfig['cookie_lifetime']
            : $sessionConfig['gc_maxlifetime'];

        if ($imageMaxLifetime > self::CACHE_CONTROL_DEFAULT_MAX_AGE) {
            $imageMaxLifetime = self::CACHE_CONTROL_DEFAULT_MAX_AGE;
        }

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

        if (isset($binary)) {
            $response->setContent($binary->getContent());
            $response->headers->set('Content-Type', $binary->getMimeType());
            $response->headers->set('Content-Length', strlen($binary->getContent()));
            $response->headers->set('Cache-Control', sprintf('max-age=%d, public', $imageMaxLifetime));
            $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, true);
            $response->headers->set('Content-Security-Policy', 'sandbox');

            return $response;
        }

        throw $this->createNotFoundException();
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


        $response = new Response(
            $binary->getContent(),
            Response::HTTP_OK,
            [
                'Content-Type' => $binary->getMimeType(),
                AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER => true,
                'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
                'Content-Security-Policy' => 'sandbox',
            ]
        );

        if (str_contains($filter, 'avatar')) {
            $sessionConfig = $this->getParameter('session.storage.options');
            $imageMaxAge = \array_key_exists('cookie_lifetime', $sessionConfig)
                ? $sessionConfig['cookie_lifetime']
                : $sessionConfig['gc_maxlifetime'];
            if ($imageMaxAge > self::CACHE_CONTROL_DEFAULT_MAX_AGE) {
                $imageMaxAge = self::CACHE_CONTROL_DEFAULT_MAX_AGE;
            }

            $response->headers->set('Cache-Control', sprintf('max-age=%d, public', $imageMaxAge));
        }

        return $response;
    }

    private function getFileById(int $id): File
    {
        /** @var File|null $file */
        $file = $this->container->get(ManagerRegistry::class)->getManagerForClass(File::class)->find(File::class, $id);
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
        if (
            $filename === $file->getFilename()
            || $filename === $file->getOriginalFilename()
            || $filename === $this->getFileNameProvider()->getFileName($file)
        ) {
            return;
        }

        throw $this->createNotFoundException('File not found');
    }

    private function assertValidFilteredImageName(File $file, string $filename, string $filterName): void
    {
        if ($filename === $this->getFileNameProvider()->getFilteredImageName($file, $filterName)) {
            return;
        }

        if (
            $this->getWebpConfiguration()->isEnabledIfSupported()
            && $filename === $this->getFileNameProvider()->getFilteredImageName($file, $filterName, 'webp')
        ) {
            return;
        }

        throw $this->createNotFoundException('File not found');
    }

    private function assertValidResizedImageName(File $file, string $filename, int $width, int $height): void
    {
        if ($filename === $this->getFileNameProvider()->getResizedImageName($file, $width, $height)) {
            return;
        }

        if (
            $this->getWebpConfiguration()->isEnabledIfSupported()
            && $filename === $this->getFileNameProvider()->getResizedImageName($file, $width, $height, 'webp')
        ) {
            return;
        }

        throw $this->createNotFoundException('File not found');
    }

    /**
     * The get action keeps the media type of the file, a file without a valid media type is served as binary data.
     */
    private function getContentType(string $action, string $mimeType): string
    {
        if (FileUrlProviderInterface::FILE_ACTION_GET !== $action) {
            return 'application/force-download';
        }

        return MimeTypesConverter::isMediaType($mimeType) ? $mimeType : 'application/octet-stream';
    }

    private function getAttachmentContentDisposition(File $file): string
    {
        $contentDisposition = 'attachment';
        $originalFilename = preg_replace(self::CONTROL_CHARACTERS_PATTERN, '', (string)$file->getOriginalFilename());
        if ($originalFilename) {
            $contentDisposition .= sprintf(';filename="%s"', addslashes($originalFilename));
        }

        return $contentDisposition;
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

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            AttachmentManager::class,
            FileManager::class,
            ImageResizeManagerInterface::class,
            FileNameProviderInterface::class,
            WebpConfiguration::class,
            ManagerRegistry::class
        ]);
    }
}
