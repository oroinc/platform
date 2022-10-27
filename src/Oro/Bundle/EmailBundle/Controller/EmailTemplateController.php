<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Handler\EmailTemplateHandler;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateContentProvider;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for the email templates related functionality.
 *
 * @Route("/emailtemplate")
 */
class EmailTemplateController extends AbstractController
{
    use RequestHandlerTrait;

    /**
     * @Route(
     *      "/{_format}",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="oro_email_emailtemplate_index",
     *      type="entity",
     *      class="OroEmailBundle:EmailTemplate",
     *      permission="VIEW"
     * )
     * @Template("@OroEmail/EmailTemplate/index.html.twig")
     */
    public function indexAction()
    {
        return [
            'entity_class' => EmailTemplate::class
        ];
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"}, defaults={"id"=0}))
     * @Acl(
     *      id="oro_email_emailtemplate_update",
     *      type="entity",
     *      class="OroEmailBundle:EmailTemplate",
     *      permission="EDIT"
     * )
     * @Template("@OroEmail/EmailTemplate/update.html.twig")
     */
    public function updateAction(EmailTemplate $entity, Request $request)
    {
        return $this->update($entity, $request, false);
    }

    /**
     * @Route("/create")
     * @Acl(
     *      id="oro_email_emailtemplate_create",
     *      type="entity",
     *      class="OroEmailBundle:EmailTemplate",
     *      permission="CREATE"
     * )
     * @Template("@OroEmail/EmailTemplate/update.html.twig")
     */
    public function createAction(Request $request)
    {
        return $this->update(new EmailTemplate(), $request);
    }

    /**
     * @Route("/clone/{id}", requirements={"id"="\d+"}, defaults={"id"=0}))
     * @AclAncestor("oro_email_emailtemplate_create")
     * @Template("@OroEmail/EmailTemplate/update.html.twig")
     */
    public function cloneAction(EmailTemplate $entity, Request $request)
    {
        return $this->update(clone $entity, $request, true);
    }

    /**
     * @Route("/preview/{id}", requirements={"id"="\d+"}, defaults={"id"=0}))
     * @Acl(
     *      id="oro_email_emailtemplate_preview",
     *      type="entity",
     *      class="OroEmailBundle:EmailTemplate",
     *      permission="VIEW"
     * )
     * @Template("@OroEmail/EmailTemplate/preview.html.twig")
     * @param Request $request
     * @param bool|int $id
     * @return array
     */
    public function previewAction(Request $request, $id = false)
    {
        if (!$id) {
            $emailTemplate = new EmailTemplate();
        } else {
            $emailTemplate = $this->getDoctrine()->getRepository(EmailTemplate::class)->find($id);
        }

        /** @var FormInterface $form */
        $form = $this->get('oro_email.form.emailtemplate');
        $form->setData($emailTemplate);

        if (in_array($request->getMethod(), array('POST', 'PUT'))) {
            $this->submitPostPutRequest($form, $request);
        }

        $localization = $form->get('activeLocalization')->getData();
        $localizedTemplate = $localization
            ? $this->get(EmailTemplateContentProvider::class)
                ->getLocalizedModel($emailTemplate, $localization)
            : $emailTemplate;

        $templateRendered = $this->get(EmailRenderer::class)->compilePreview($localizedTemplate);

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
        if ($this->get(EmailTemplateHandler::class)->process($entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('oro.email.controller.emailtemplate.saved.message')
            );

            return $this->get(Router::class)->redirect($entity);
        }

        return [
            'entity'  => $entity,
            'form'    => $this->get('oro_email.form.emailtemplate')->createView(),
            'isClone' => $isClone
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                EmailTemplateContentProvider::class,
                EmailRenderer::class,
                TranslatorInterface::class,
                Router::class,
                'oro_email.form.emailtemplate' => Form::class,
                EmailTemplateHandler::class,
            ]
        );
    }
}
