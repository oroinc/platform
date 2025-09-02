<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Oro\Bundle\EmailBundle\Twig\EmailTemplateAttachmentVariablesProvider;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Provides email template attachments choices for the email template form.
 */
#[Route(path: '/emailtemplate/ajax')]
final class EmailTemplateAttachmentsController extends AbstractController
{
    use RequestHandlerTrait;

    #[Route(
        path: '/get-attachment-choices/{entityName}',
        name: 'oro_email_emailtemplate_ajax_get_attachment_choices',
        methods: ['POST']
    )]
    #[AclAncestor('oro_email_emailtemplate_create')]
    public function getAttachmentChoicesAction(string $entityName): Response|array
    {
        /** @var EmailTemplateAttachmentVariablesProvider $emailTemplateAttachmentVariablesProvider */
        $emailTemplateAttachmentVariablesProvider = $this->container
            ->get(EmailTemplateAttachmentVariablesProvider::class);

        $attachmentVariables = $emailTemplateAttachmentVariablesProvider->getAttachmentVariables($entityName);
        $choices = [];
        foreach ($attachmentVariables as $key => $value) {
            $choices[] = ['value' => $key, 'label' => $value['label']];
        }

        return new JsonResponse(['successful' => true, 'choices' => $choices]);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                EmailTemplateAttachmentVariablesProvider::class,
            ]
        );
    }
}
