<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\AttachmentBundle\Entity\File;

use Doctrine\Common\Collections\Collection;

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
        list($parentClass, $fieldName, $parentId, $type, $filename) = $this->get(
            'oro_attachment.manager'
        )->decodeAttachmentUrl($codedString);
        $parentEntity = $this->getDoctrine()->getRepository($parentClass)->find($parentId);
        if (!$this->get('oro_security.security_facade')->isGranted('VIEW', $parentEntity)) {
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
    public function getResizedAttachmentImageAction($id, $width, $height, $filename)
    {
        $file = $this->getFileByIdAndFileName($id, $filename);
        $thumbnail = $this->get('oro_attachment.thumbnail_factory')->createThumbnail(
            $this->get('oro_attachment.file_manager')->getContent($file),
            $width,
            $height
        );

        return $this->get('liip_imagine.cache.manager')->store(
            new Response((string)$thumbnail->getImage(), 200, ['Content-Type' => $file->getMimeType()]),
            substr($this->getRequest()->getPathInfo(), 1),
            $thumbnail->getFilter()
        );
    }

    /**
     * @Route("media/cache/attachment/resize/{id}/{filter}/{filename}",
     *   name="oro_filtered_attachment",
     *   requirements={"id"="\d+"}
     * )
     */
    public function getFilteredImageAction($id, $filter, $filename)
    {
        $file = $this->getFileByIdAndFileName($id, $filename);
        $image = $this->get('oro_attachment.image_factory')->createImage(
            $this->get('oro_attachment.file_manager')->getContent($file),
            $filter
        );

        return $this->get('liip_imagine.cache.manager')->store(
            new Response((string)$image, 200, ['Content-Type' => $file->getMimeType()]),
            substr($this->getRequest()->getPathInfo(), 1),
            $filter
        );
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
