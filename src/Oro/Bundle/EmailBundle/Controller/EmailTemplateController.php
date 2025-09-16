<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Handler\EmailTemplateHandler;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Provider\TranslatedEmailTemplateProvider;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for the email templates related functionality.
 */
#[Route(path: '/emailtemplate')]
class EmailTemplateController extends AbstractController
{
    use RequestHandlerTrait;

    #[Route(path: '/{_format}', requirements: ['_format' => 'html|json'], defaults: ['_format' => 'html'])]
    #[Template('@OroEmail/EmailTemplate/index.html.twig')]
    #[Acl(id: 'oro_email_emailtemplate_index', type: 'entity', class: EmailTemplate::class, permission: 'VIEW')]
    public function indexAction()
    {
        return [
            'entity_class' => EmailTemplate::class
        ];
    }

    #[Route(path: '/update/{id}', requirements: ['id' => '\d+'], defaults: ['id' => 0])]
    #[Template('@OroEmail/EmailTemplate/update.html.twig')]
    #[Acl(id: 'oro_email_emailtemplate_update', type: 'entity', class: EmailTemplate::class, permission: 'EDIT')]
    public function updateAction(EmailTemplate $entity, Request $request)
    {
        return $this->update($entity, $request, false);
    }

    #[Route(path: '/create')]
    #[Template('@OroEmail/EmailTemplate/update.html.twig')]
    #[Acl(id: 'oro_email_emailtemplate_create', type: 'entity', class: EmailTemplate::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        return $this->update(new EmailTemplate(), $request);
    }

    #[Route(path: '/clone/{id}', requirements: ['id' => '\d+'], defaults: ['id' => 0])]
    #[Template('@OroEmail/EmailTemplate/update.html.twig')]
    #[AclAncestor('oro_email_emailtemplate_create')]
    public function cloneAction(EmailTemplate $entity, Request $request)
    {
        return $this->update(clone $entity, $request, true);
    }

    /**
     * @param Request $request
     * @param bool|int $id
     * @return array
     */
    #[Route(path: '/preview/{id}', requirements: ['id' => '\d+'], defaults: ['id' => 0])]
    #[Template('@OroEmail/EmailTemplate/preview.html.twig')]
    #[Acl(id: 'oro_email_emailtemplate_preview', type: 'entity', class: EmailTemplate::class, permission: 'VIEW')]
    public function previewAction(Request $request, $id = false)
    {
        if (!$id) {
            $emailTemplate = new EmailTemplate();
        } else {
            $emailTemplate = $this->container->get('doctrine')->getRepository(EmailTemplate::class)->find($id);
        }

        /** @var FormInterface $form */
        $form = $this->container->get('oro_email.form.emailtemplate');
        $form->setData($emailTemplate);

        if (in_array($request->getMethod(), array('POST', 'PUT'))) {
            $this->submitPostPutRequest($form, $request);
        }

        $localization = $form->get('activeLocalization')->getData();
        $localizedTemplate = $this->container->get(TranslatedEmailTemplateProvider::class)
            ->getTranslatedEmailTemplate($emailTemplate, $localization);

        $templateRendered = $this->container->get(EmailRenderer::class)->compilePreview($localizedTemplate);

        return [
            'content'     => $templateRendered,
            'contentType' => $emailTemplate->getType()
        ];
    }

    /**
     * @param EmailTemplate $entity
     * @param Request $request
     * @param bool $isClone
     * @return array
     */
    protected function update(EmailTemplate $entity, Request $request, $isClone = false)
    {
        if ($this->container->get(EmailTemplateHandler::class)->process($entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)
                    ->trans('oro.email.controller.emailtemplate.saved.message')
            );

            return $this->container->get(Router::class)->redirect($entity);
        }

        return [
            'entity'  => $entity,
            'form'    => $this->container->get('oro_email.form.emailtemplate')->createView(),
            'isClone' => $isClone
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatedEmailTemplateProvider::class,
                EmailRenderer::class,
                TranslatorInterface::class,
                Router::class,
                'oro_email.form.emailtemplate' => Form::class,
                EmailTemplateHandler::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
