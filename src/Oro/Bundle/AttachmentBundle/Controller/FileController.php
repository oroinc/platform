<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Exception\InvalidAttachmentEncodedParametersException;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Controller class with actions that work with files
 */
class FileController extends Controller
{
    /**
     * @Route("attachment/{codedString}.{extension}",
     *   name="oro_attachment_file",
     *   requirements={"extension"="\w+"}
     * )
     */
    public function getAttachmentAction($codedString, $extension)
    {
        list($parentClass, $fieldName, $parentId, $type, $filename) = $this->decodeAttachmentUrl($codedString);

        $parentEntity = $this->getDoctrine()->getRepository($parentClass)->find($parentId);

        if (!$parentEntity) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted('VIEW', $parentEntity)) {
            throw $this->createAccessDeniedException();
        }

        $accessor   = PropertyAccess::createPropertyAccessor();
        $attachment = $accessor->getValue($parentEntity, $fieldName);
        if ($attachment instanceof Collection) {
            foreach ($attachment as $attachmentEntity) {
                if ($attachmentEntity->getOriginalFilename() === $filename) {
                    $attachment = $attachmentEntity;
                    break;
                }
            }
        }

        if ($attachment instanceof Collection || $attachment->getOriginalFilename() !== $filename) {
            throw $this->createNotFoundException();
        }

        return $this->getFileAction($attachment->getId(), $attachment->getFilename(), $type);
    }

    /**
     * @Route("attachment/{action}/{id}/{filename}",
     *   name="oro_attachment_get_file",
     *   requirements={"id"="\d+", "action"="(get|download)"}
     * )
     * @param int $id
     * @param string $filename
     * @param string $action
     *
     * @return Response
     */
    public function getFileAction(int $id, string $filename, string $action): Response
    {
        $file = $this->getFileByIdAndFileName($id, $filename);

        $response = new Response();
        $response->headers->set('Cache-Control', 'public');

        if ($action === FileUrlProviderInterface::FILE_ACTION_GET) {
            $response->headers->set('Content-Type', $file->getMimeType() ?: 'application/force-download');
        } else {
            $response->headers->set('Content-Type', 'application/force-download');
            $response->headers->set(
                'Content-Disposition',
                sprintf('attachment;filename="%s"', addslashes($file->getOriginalFilename()))
            );
        }

        $response->headers->set('Content-Length', $file->getFileSize());
        $response->setContent($this->get('oro_attachment.file_manager')->getContent($file));

        return $response;
    }

    /**
     * @Route("media/cache/attachment/resize/{id}/{width}/{height}/{filename}",
     *   name="oro_resize_attachment",
     *   requirements={"id"="\d+", "width"="\d+", "height"="\d+"}
     * )
     * @param int $id
     * @param int $width
     * @param int $height
     * @param string $filename
     *
     * @return Response
     */
    public function getResizedAttachmentImageAction($id, $width, $height, $filename, Request $request)
    {
        $file = $this->getFileByIdAndFileName($id, $filename);

        /** @var ImageResizeManagerInterface $resizeManager */
        $resizeManager = $this->get('oro_attachment.manager.image_resize');
        $binary = $resizeManager->resize($file, $width, $height);
        if (!$binary) {
            throw $this->createNotFoundException();
        }

        return new Response($binary->getContent(), Response::HTTP_OK, ['Content-Type' => $binary->getMimeType()]);
    }

    /**
     * @Route("media/cache/attachment/resize/{filter}/{filterMd5}/{id}/{filename}",
     *   name="oro_filtered_attachment",
     *   requirements={"id"="\d+", "filterMd5"="^[0-9a-f]{32}$"}
     * )
     * @param int $id
     * @param string $filter
     * @param string $filename
     *
     * @return Response
     */
    public function getFilteredImageAction($id, $filter, $filename, Request $request)
    {
        $file = $this->getFileByIdAndFileName($id, $filename);

        /** @var ImageResizeManagerInterface $resizeManager */
        $resizeManager = $this->get('oro_attachment.manager.image_resize');
        $binary = $resizeManager->applyFilter($file, $filter);
        if (!$binary) {
            throw $this->createNotFoundException();
        }

        return new Response($binary->getContent(), Response::HTTP_OK, ['Content-Type' => $binary->getMimeType()]);
    }

    /**
     * @param int $id
     * @param string $fileName
     *
     * @return File
     *
     * @throws NotFoundHttpException
     */
    protected function getFileByIdAndFileName($id, $fileName)
    {
        /** @var File $file */
        $file = $this->get('doctrine')->getRepository(File::class)->find($id);
        $filenameProvider = $this->get('oro_attachment.provider.attachment_file_name_provider');
        if (!$file || (
            $filenameProvider->getFileName($file) !== $fileName
            && $fileName !== $file->getFilename()
            && $fileName !== $file->getOriginalFilename()
        )) {
            throw $this->createNotFoundException('File not found');
        }

        if (!$this->isGranted('VIEW', $file)) {
            throw $this->createAccessDeniedException();
        }

        return $file;
    }

    /**
     * @param $codedString
     *
     * @return array
     */
    private function decodeAttachmentUrl($codedString)
    {
        try {
            $decodedParams = $this->get('oro_attachment.manager')->decodeAttachmentUrl($codedString);
        } catch (InvalidAttachmentEncodedParametersException $exception) {
            throw $this->createNotFoundException('File not found');
        }

        return $decodedParams;
    }
}
