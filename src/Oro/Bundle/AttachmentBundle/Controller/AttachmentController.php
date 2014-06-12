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
}
