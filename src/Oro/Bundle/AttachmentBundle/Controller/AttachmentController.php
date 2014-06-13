<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;

class AttachmentController extends Controller
{
    /**
     * @Route("/{type}/{id}/{filename}", name="oro_attachment_file", requirements={"id"="\d+", "type"="get|download"})
     */
    public function getAttachmentAction($type, Attachment $attachment)
    {
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
     * @Route("/resize/{id}/{width}/{height}/{filename}", name="oro_resize_attachment", requirements={"id"="\d+"})
     */
    public function getResizedAttachmentImageAction(Attachment $attachment, $width, $height)
    {
        $path = substr($this->getRequest()->getPathInfo(), 1);
        $filterName = 'attachment_' . $width . '_' . $height;

        $this->get('liip_imagine.filter.configuration')->set(
            $filterName,
            ['filters' => ['thumbnail' => ['size' => [$width, $height]]]]
        );
        $binary = $this->get('liip_imagine')->load($this->get('oro_attachment.manager')->getContent($attachment));
        $filteredBinary = $this->get('liip_imagine.filter.manager')->applyFilter($binary, $filterName);
        $response = new Response($filteredBinary, 200, array('Content-Type' => $attachment->getMimeType()));

        return $this->get('liip_imagine.cache.manager')->store($response, $path, $filterName);
    }
}
