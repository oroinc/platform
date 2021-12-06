<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller with actions that work with files.
 */
class FileController extends AbstractController
{
    public function getFileAction(int $id, string $filename, string $action, Request $request): Response
    {
        $file = $this->getFileByIdAndFileName($id, $filename);
        $this->unlockSession($request);

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
        $file = $this->getFileByIdAndFileName($id, $filename);
        $this->unlockSession($request);

        $binary = $this->getImageResizeManager()->resize($file, $width, $height, $this->getFormat($file, $filename));
        if (!$binary) {
            throw $this->createNotFoundException();
        }

        return new Response($binary->getContent(), Response::HTTP_OK, ['Content-Type' => $binary->getMimeType()]);
    }

    public function getFilteredImageAction(int $id, string $filter, string $filename, Request $request): Response
    {
        $file = $this->getFileByIdAndFileName($id, $filename);
        $this->unlockSession($request);

        $binary = $this->getImageResizeManager()->applyFilter($file, $filter, $this->getFormat($file, $filename));
        if (!$binary) {
            throw $this->createNotFoundException();
        }

        return new Response($binary->getContent(), Response::HTTP_OK, ['Content-Type' => $binary->getMimeType()]);
    }

    private function getFormat(File $file, string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $format = '';
        if (strtolower($file->getExtension()) !== $extension) {
            $format = $extension;
        }

        return $format;
    }

    private function getFileByIdAndFileName(int $id, string $filename): File
    {
        /** @var File|null $file */
        $file = $this->getDoctrine()->getManagerForClass(File::class)->find(File::class, $id);
        if (!$file || !$this->isValidFilename($file, $filename)) {
            throw $this->createNotFoundException('File not found');
        }

        if (!$this->isGranted('VIEW', $file)) {
            throw $this->createAccessDeniedException();
        }

        return $file;
    }

    private function isValidFilename(File $file, string $filename): bool
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (strtolower($extension) !== $file->getExtension()) {
            $filename = $this->stripExtension($filename);
        }

        return $filename === $file->getFilename()
            || $this->stripExtension($filename) === $file->getFilename()
            || $filename === $file->getOriginalFilename()
            || $filename === $this->getFileNameProvider()->getFileName($file);
    }

    private function stripExtension(string $filename): string
    {
        return pathinfo($filename, PATHINFO_FILENAME);
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
        ]);
    }
}
