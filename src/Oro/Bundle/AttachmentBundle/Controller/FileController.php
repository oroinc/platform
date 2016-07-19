<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Liip\ImagineBundle\Model\Binary;

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
        $response->setContent($this->get('oro_attachment.manager')->getContent($attachment));

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
        $attachment = $this->getFileByIdAndFileName($id, $filename);
        $path       = $this->get('request_stack')->getCurrentRequest()->getPathInfo();
        $filterName = 'attachment_' . $width . '_' . $height;
        $cacheResolverName = $this->getParameter('oro_attachment.imagine.cache.resolver.custom_web_path.name');

        $this->get('liip_imagine.filter.configuration')->set(
            $filterName,
            [
                'filters' => [
                    'thumbnail' => [
                        'size' => [$width, $height]
                    ]
                ]
            ]
        );

        $binary = $this->createBinaryFromFile($attachment);
        $filteredBinary = $this->get('liip_imagine.filter.manager')->applyFilter($binary, $filterName);
        $this->get('liip_imagine.cache.manager')->store($filteredBinary, $path, $filterName, $cacheResolverName);

        return new Response(
            $filteredBinary->getContent(),
            Response::HTTP_OK,
            [
                'Content-Type' => $filteredBinary->getMimeType()
            ]
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
        $attachment     = $this->getFileByIdAndFileName($id, $filename);
        $path           = $this->get('request_stack')->getCurrentRequest()->getPathInfo();
        $binary         = $this->createBinaryFromFile($attachment);
        $cacheResolverName = $this->getParameter('oro_attachment.imagine.cache.resolver.custom_web_path.name');

        $this->get('oro_layout.provider.image_filter')->load();

        $filteredBinary = $this->get('liip_imagine.filter.manager')->applyFilter($binary, $filter);
        $this->get('liip_imagine.cache.manager')->store($filteredBinary, $path, $filter, $cacheResolverName);

        return new Response(
            $filteredBinary->getContent(),
            Response::HTTP_OK,
            [
                'Content-Type' => $filteredBinary->getMimeType()
            ]
        );
    }

    /**
     * Get file
     *
     * @param $id
     * @param $fileName
     * @return File
     * @throws NotFoundHttpException
     */
    protected function getFileByIdAndFileName($id, $fileName)
    {
        $attachment = $this->get('doctrine')->getRepository('OroAttachmentBundle:File')->findOneBy(
            [
                'id'               => $id,
                'originalFilename' => $fileName
            ]
        );
        if (!$attachment) {
            throw new NotFoundHttpException('File not found');
        }

        return $attachment;
    }

    /**
     * @param File $file
     * @return Binary
     */
    protected function createBinaryFromFile(File $file)
    {
        $mimeType = $file->getMimeType();
        $format = $this->get('liip_imagine.extension_guesser')->guess($mimeType);
        $content = $this->get('oro_attachment.manager')->getContent($file);

        return new Binary($content, $mimeType, $format);
    }
}
