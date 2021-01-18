<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
    public function updateAction(EmailTemplate $entity, $isClone = false)
    {
        return $this->update($entity, $isClone);
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
    public function createAction()
    {
        return $this->update(new EmailTemplate());
    }

    /**
     * @Route("/clone/{id}", requirements={"id"="\d+"}, defaults={"id"=0}))
     * @AclAncestor("oro_email_emailtemplate_create")
     * @Template("@OroEmail/EmailTemplate/update.html.twig")
     */
    public function cloneAction(EmailTemplate $entity)
    {
        return $this->update(clone $entity, true);
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
            /** @var EntityManager $em */
            $em = $this->get('doctrine.orm.entity_manager');
            $emailTemplate = $em->getRepository('Oro\Bundle\EmailBundle\Entity\EmailTemplate')->find($id);
        }

        /** @var FormInterface $form */
        $form = $this->get('oro_email.form.emailtemplate');
        $form->setData($emailTemplate);

        if (in_array($request->getMethod(), array('POST', 'PUT'))) {
            $this->submitPostPutRequest($form, $request);
        }

        $localization = $form->get('activeLocalization')->getData();
        $localizedTemplate = $localization
            ? $this->get('oro_email.provider.email_template_content_provider')
                ->getLocalizedModel($emailTemplate, $localization)
            : $emailTemplate;

        $templateRendered = $this->get('oro_email.email_renderer')->compilePreview($localizedTemplate);

        return array(
            'content'     => $templateRendered,
            'contentType' => $emailTemplate->getType()
        );
    }

    /**
     * @param EmailTemplate $entity
     * @param bool $isClone
     * @return array
     */
    protected function update(EmailTemplate $entity, $isClone = false)
    {
        if ($this->get('oro_email.form.handler.emailtemplate')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.email.controller.emailtemplate.saved.message')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        return array(
            'entity'  => $entity,
            'form'    => $this->get('oro_email.form.emailtemplate')->createView(),
            'isClone' => $isClone
        );
    }
}
