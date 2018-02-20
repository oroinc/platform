<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
        list($parentClass, $fieldName, $parentId, $type, $filename) = $this->get('oro_attachment.manager')
            ->decodeAttachmentUrl($codedString);
        $parentEntity = $this->getDoctrine()->getRepository($parentClass)->find($parentId);
        if (!$this->isGranted('VIEW', $parentEntity)) {
            throw new AccessDeniedException();
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
            throw new NotFoundHttpException();
        }

        $response = new Response();
        $response->headers->set('Cache-Control', 'public');

        if ($type == 'get') {
            $response->headers->set('Content-Type', $attachment->getMimeType() ? : 'application/force-download');
        } else {
            $response->headers->set('Content-Type', 'application/force-download');
            $response->headers->set(
                'Content-Disposition',
                sprintf('attachment;filename="%s"', $attachment->getOriginalFilename())
            );
        }

        $response->headers->set('Content-Length', $attachment->getFileSize());
        $response->setContent($this->get('oro_attachment.file_manager')->getContent($attachment));

        return $response;
    }

    /**
     * @Route("media/cache/attachment/resize/{id}/{width}/{height}/{filename}",
     *   name="oro_resize_attachment",
     *   requirements={"id"="\d+", "width"="\d+", "height"="\d+"}
     * )
     */
    public function getResizedAttachmentImageAction($id, $width, $height, $filename, Request $request)
    {
        $file = $this->getFileByIdAndFileName($id, $filename);
        $thumbnail = $this->get('oro_attachment.thumbnail_factory')->createThumbnail(
            $this->get('oro_attachment.file_manager')->getContent($file),
            $width,
            $height
        );

        $image = $thumbnail->getBinary();
        $imageContent = $image->getContent();
        $this->get('oro_attachment.media_cache_manager')->store($imageContent, $request->getPathInfo());

        return new Response($imageContent, Response::HTTP_OK, ['Content-Type' => $image->getMimeType()]);
    }

    /**
     * @Route("media/cache/attachment/resize/{id}/{filter}/{filename}",
     *   name="oro_filtered_attachment",
     *   requirements={"id"="\d+"}
     * )
     */
    public function getFilteredImageAction($id, $filter, $filename, Request $request)
    {
        if (!$file = $this->getFileByIdAndFileName($id, $filename)) {
            throw $this->createNotFoundException('Image not found in the database');
        }

        if (!$image = $this->get('oro_attachment.image_resizer')->resizeImage($file, $filter)) {
            throw $this->createNotFoundException('Image not found in the filesystem');
        }

        $this->get('oro_attachment.media_cache_manager')->store($image->getContent(), $request->getPathInfo());

        return new Response($image->getContent(), Response::HTTP_OK, ['Content-Type' => $image->getMimeType()]);
    }

    /**
     * @param int    $id
     * @param string $fileName
     *
     * @return File
     *
     * @throws NotFoundHttpException
     */
    protected function getFileByIdAndFileName($id, $fileName)
    {
        $file = $this->get('doctrine')->getRepository('OroAttachmentBundle:File')->find($id);
        if (!$file || ($file->getFilename() !== $fileName && $file->getOriginalFilename() !== $fileName)) {
            throw new NotFoundHttpException('File not found');
        }

        return $file;
    }
}
