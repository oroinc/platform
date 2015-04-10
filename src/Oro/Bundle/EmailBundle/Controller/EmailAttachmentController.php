<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as AttachmentModel;

class EmailAttachmentController extends Controller
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
        $attachments = [];

        if ($request->isMethod('POST')) {
            // todo refactor and move to service
            $files = $request->files;
            if ($files) {
                foreach ($files as $file) {
                    $emailAttachment = new EmailAttachment();
                    $emailAttachment->setUploadedFile($file);

                    $attachmentContent = new EmailAttachmentContent();
                    $attachmentContent->setContent(
                        base64_encode(file_get_contents($emailAttachment->getUploadedFile()->getRealPath()))
                    );
                    $attachmentContent->setContentTransferEncoding('base64');
                    $attachmentContent->setEmailAttachment($emailAttachment);

                    $emailAttachment->setContent($attachmentContent);
                    $emailAttachment->setContentType($emailAttachment->getUploadedFile()->getMimeType());
                    $emailAttachment->setFileName($emailAttachment->getUploadedFile()->getClientOriginalName());

                    $this->get('doctrine')->getManager()->persist($emailAttachment);

                    // todo horror begins
                    $model = new AttachmentModel();
                    $model->setFileSize(strlen($emailAttachment->getContent()->getContent())); // todo this is base64!
                    $model->setModified($this->get('oro_locale.formatter.date_time')->format(new \DateTime('now')));

                    $attachment = [
                        'id' => $emailAttachment->getId(),
                        'type' => AttachmentModel::TYPE_EMAIL_ATTACHMENT,
                        'fileName' => $emailAttachment->getFileName(),
                        'info' => $model->getInfo(),
                        'checked' => true,
                    ];
                    $attachments[] = $attachment;
                }
            }
        }
        $this->get('doctrine')->getManager()->flush();

        return new JsonResponse(compact('attachments'));
    }
}
