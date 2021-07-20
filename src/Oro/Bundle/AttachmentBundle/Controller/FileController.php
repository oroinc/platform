<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManager;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller with actions that work with files.
 */
class FileController extends AbstractController
{
    /**
     * @Route("attachment/{action}/{id}/{filename}",
     *   name="oro_attachment_get_file",
     *   requirements={"id"="\d+", "action"="(get|download)"}
     * )
     */
    public function getFileAction(int $id, string $filename, string $action): Response
    {
        $file = $this->getFileByIdAndFileName($id, $filename);
        $this->unlockSession();

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

    /**
     * @Route("media/cache/attachment/resize/{id}/{width}/{height}/{filename}",
     *   name="oro_resize_attachment",
     *   requirements={"id"="\d+", "width"="\d+", "height"="\d+"}
     * )
     */
    public function getResizedAttachmentImageAction(int $id, int $width, int $height, string $filename): Response
    {
        $file = $this->getFileByIdAndFileName($id, $filename);
        $this->unlockSession();

        $binary = $this->getImageResizeManager()->resize($file, $width, $height);
        if (!$binary) {
            throw $this->createNotFoundException();
        }

        return new Response($binary->getContent(), Response::HTTP_OK, ['Content-Type' => $binary->getMimeType()]);
    }

    /**
     * @Route("media/cache/attachment/filter/{filter}/{filterMd5}/{id}/{filename}",
     *   name="oro_filtered_attachment",
     *   requirements={"id"="\d+", "filterMd5"="^[0-9a-f]{32}$"}
     * )
     */
    public function getFilteredImageAction(int $id, string $filter, string $filename): Response
    {
        $file = $this->getFileByIdAndFileName($id, $filename);
        $this->unlockSession();

        $binary = $this->getImageResizeManager()->applyFilter($file, $filter);
        if (!$binary) {
            throw $this->createNotFoundException();
        }

        return new Response($binary->getContent(), Response::HTTP_OK, ['Content-Type' => $binary->getMimeType()]);
    }

    private function getFileByIdAndFileName(int $id, string $fileName): File
    {
        /** @var File|null $file */
        $file = $this->getDoctrine()->getManagerForClass(File::class)->find(File::class, $id);
        if (null === $file
            || (
                $fileName !== $file->getFilename()
                && $fileName !== $file->getOriginalFilename()
                && $fileName !== $this->getFileNameProvider()->getFileName($file)
            )) {
            throw $this->createNotFoundException('File not found');
        }

        if (!$this->isGranted('VIEW', $file)) {
            throw $this->createAccessDeniedException();
        }

        return $file;
    }

    private function unlockSession(): void
    {
        $session = $this->getSession();
        if (null !== $session && $session->isStarted()) {
            $session->save();
        }
    }

    private function getFileManager(): FileManager
    {
        return $this->get('oro_attachment.file_manager');
    }

    private function getImageResizeManager(): ImageResizeManagerInterface
    {
        return $this->get('oro_attachment.manager.image_resize');
    }

    private function getFileNameProvider(): FileNameProviderInterface
    {
        return $this->get('oro_attachment.provider.file_name');
    }

    private function getSession(): ?SessionInterface
    {
        return $this->get('session');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'oro_attachment.file_manager'         => FileManager::class,
            'oro_attachment.manager.image_resize' => ImageResizeManager::class,
            'oro_attachment.provider.file_name'   => FileNameProviderInterface::class
        ]);
    }
}
