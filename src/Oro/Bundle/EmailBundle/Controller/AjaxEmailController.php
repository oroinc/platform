<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Form\Handler\EmailHandler;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contains methods for ajax actions related to the Email entity.
 */
#[Route([
    "/ajax",
    "name" => "oro_email_ajax",
])]
class AjaxEmailController extends AbstractController
{
    #[Route(
        path: '/compile-email',
        name: 'oro_email_ajax_email_compile',
        methods: ['POST']
    )]
    #[AclAncestor('oro_email_email_create')]
    #[CsrfProtection()]
    public function compileEmailAction(Request $request): JsonResponse
    {
        $emailModel = $this->getEmailModelBuilder()->createEmailModel();
        $emailHandler = $this->getEmailModelHandler();
        $emailForm = $emailHandler->createForm(
            $emailModel,
            [
                'csrf_protection' => false, // CSRF protection is enabled for the whole method.
                'validation_groups' => false, // Validation is not needed as we use the form just to get
                // a rendered email model.
            ]
        );
        $emailHandler->handleRequest($emailForm, $request);

        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            try {
                // Builds form again using the submitted email model.
                $emailForm = $emailHandler->createForm($emailModel);
            } catch (EmailTemplateCompilationException $exception) {
                return new JsonResponse(
                    [
                        'reason' => $this->container->get(TranslatorInterface::class)->trans(
                            'oro.email.emailtemplate.failed_to_compile'
                        ),
                    ],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        $attachmentsFormView = $emailForm->get('attachments')->createView();

        return new JsonResponse(
            [
                'subject' => $emailModel->getSubject(),
                'body' => $emailModel->getBody(),
                'type' => $emailModel->getType(),
                'attachments' => $attachmentsFormView->vars['entity_attachments_array'] ?? [],
            ],
            Response::HTTP_OK
        );
    }

    private function getEmailModelBuilder(): EmailModelBuilder
    {
        return $this->container->get(EmailModelBuilder::class);
    }

    private function getEmailModelHandler(): EmailHandler
    {
        return $this->container->get(EmailHandler::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            EmailModelBuilder::class,
            EmailHandler::class,
            TranslatorInterface::class,
        ];
    }
}
