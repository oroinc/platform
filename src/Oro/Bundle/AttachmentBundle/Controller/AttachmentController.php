<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;

class AttachmentController extends Controller
{
    /**
     * @Route(
     *      "attachment/view/widget/{entityClass}/{entityId}",
     *      name="oro_attachment_widget_attachments"
     * )
     *
     * @Template("OroAttachmentBundle:Attachment:attachments.html.twig")
     */
    public function widgetAction($entityClass, $entityId)
    {
        $entityClass = $this->get('oro_entity.routing_helper')->decodeClassName($entityClass);
        return [
            'entityId' => $entityId,
            'entityField' => ExtendHelper::buildAssociationName($entityClass),
            'entityClass' => $entityClass
        ];
    }

    /**
     * @Route("attachment/{codedString}.{extension}",
     *   name="oro_attachment_file",
     *   requirements={"extension"="\w+"}
     * )
     */
    public function getAttachmentAction($codedString, $extension)
    {
        list($parentClass, $fieldName, $parentId, $type, $filename) =
            $this->get('oro_attachment.manager')->decodeAttachmentUrl($codedString);
        $parentEntity = $this->getDoctrine()->getRepository($parentClass)->find($parentId);
        if (!$this->get('oro_security.security_facade')->isGranted('VIEW', $parentEntity)) {
            throw new AccessDeniedException();
        }
        $accessor = PropertyAccess::createPropertyAccessor();
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
     *  name="oro_resize_attachment",
     *  requirements={"id"="\d+", "width"="\d+", "height"="\d+"}
     * )
     */
    public function getResizedAttachmentImageAction($id, $width, $height, $filename)
    {
        $attachment = $this->getAttachmentByIdAndFileName($id, $filename);
        $path = substr($this->getRequest()->getPathInfo(), 1);
        $filterName = 'attachment_' . $width . '_' . $height;

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
        $binary = $this->get('liip_imagine')->load($this->get('oro_attachment.manager')->getContent($attachment));
        $filteredBinary = $this->get('liip_imagine.filter.manager')->applyFilter($binary, $filterName);
        $response = new Response($filteredBinary, 200, array('Content-Type' => $attachment->getMimeType()));

        return $this->get('liip_imagine.cache.manager')->store($response, $path, $filterName);
    }

    /**
     * @Route("media/cache/attachment/resize/{id}/{filter}/{filename}",
     *  name="oro_filtered_attachment",
     *  requirements={"id"="\d+"}
     * )
     */
    public function getFilteredImageAction($id, $filter, $filename)
    {
        $attachment = $this->getAttachmentByIdAndFileName($id, $filename);
        $path = substr($this->getRequest()->getPathInfo(), 1);
        $binary = $this->get('liip_imagine')->load($this->get('oro_attachment.manager')->getContent($attachment));
        $filteredBinary = $this->get('liip_imagine.filter.manager')->applyFilter($binary, $filter);
        $response = new Response($filteredBinary, 200, array('Content-Type' => $attachment->getMimeType()));

        return $this->get('liip_imagine.cache.manager')->store($response, $path, $filter);
    }

    /**
     * Get attachment
     *
     * @param $id
     * @param $fileName
     * @return Attachment
     * @throws NotFoundHttpException
     */
    protected function getAttachmentByIdAndFileName($id, $fileName)
    {
        $attachment = $this->get('doctrine')->getRepository('OroAttachmentBundle:Attachment')->findOneBy(
            [
                'id' => $id,
                'originalFilename' => $fileName
            ]
        );
        if (!$attachment) {
            throw new NotFoundHttpException('File not found');
        }

        return $attachment;
    }
}
