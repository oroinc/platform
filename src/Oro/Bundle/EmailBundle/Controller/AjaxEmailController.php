<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Form\Handler\EmailHandler;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
        $form = $emailHandler->createForm(
            $emailModel,
            [
                'csrf_protection' => false, // CSRF protection is enabled for the whole method.
                'validation_groups' => false, // Validation is not needed as we use the form just to get
                // a rendered email model.
            ]
        );
        $emailHandler->handleRequest($form, $request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Builds form again using the submitted email model.
            $emailHandler->createForm($emailModel);
        }

        return new JsonResponse(
            [
                'subject' => $emailModel->getSubject(),
                'body' => $emailModel->getBody(),
                'type' => $emailModel->getType(),
            ],
            200
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

    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            EmailModelBuilder::class,
            EmailHandler::class,
        ];
    }
}
