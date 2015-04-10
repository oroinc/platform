<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class EmailAttachmentController
{
    /**
     * @Route(
     *      "/emailattachment/upload",
     *      name="oro_email_attachment_upload"
     * )
     *
     * @AclAncestor("oro_email_create")
     */
    public function uploadAction(Request $request)
    {
        if ($request->isMethod('POST')) {
            // todo move to service
            $files = $request->files;
            if ($files) {
                foreach ($files as $file) {
                    $emailAttachment = new EmailAttachment();
                    $emailAttachment->setUploadedFile($file);
                }
            }
        }

        return new JsonResponse();
    }
}
