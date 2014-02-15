<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;

use Doctrine\ORM\EntityManager;
use FOS\Rest\Util\Codes;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormType;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class EmbeddedFormController extends Controller
{
    /**
     * @Route(name="oro_embedded_form_list")
     * @Template()
     * @AclAncestor("oro_embedded_form_view")
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("create", name="oro_embedded_form_create")
     * @Template("OroEmbeddedFormBundle:EmbeddedForm:update.html.twig")
     * @Acl(
     *      id="oro_embedded_form_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroEmbeddedFormBundle:EmbeddedForm"
     * )
     */
    public function createAction()
    {
        return $this->update();
    }

    /**
     * @Route("delete/{id}", name="oro_embedded_form_delete", requirements={"id"="[-\d\w]+"})
     * @Acl(
     *      id="oro_embedded_form_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroEmbeddedFormBundle:EmbeddedForm"
     * )
     */
    public function deleteAction(EmbeddedForm $entity)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $em->remove($entity);
        $em->flush();

        return new JsonResponse('', Codes::HTTP_OK);
    }

    /**
     * @Route("default-data/{formType}", name="oro_embedded_form_default_data")
     */
    public function defaultDataAction($formType)
    {
        /** @var EmbeddedFormManager $formManager */
        $formManager = $this->get('oro_embedded_form.manager');
        $css = $formManager->getDefaultCssByType($formType);
        $successMessage = $formManager->getDefaultSuccessMessageByType($formType);

        return new JsonResponse([
            'css' => $css,
            'successMessage' => $successMessage
        ], Codes::HTTP_OK);
    }

    /**
     * @Route("update/{id}", name="oro_embedded_form_update", requirements={"id"="[-\d\w]+"})
     * @Template()
     * @Acl(
     *      id="oro_embedded_form_edit",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroEmbeddedFormBundle:EmbeddedForm"
     * )
     */
    public function updateAction(EmbeddedForm $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route("view/{id}", name="oro_embedded_form_view", requirements={"id"="[-\d\w]+"})
     * @Template()
     * @Acl(
     *      id="oro_embedded_form_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroEmbeddedFormBundle:EmbeddedForm"
     * )
     */
    public function viewAction(EmbeddedForm $entity)
    {
        return [
            'entity' => $entity
        ];
    }

    /**
     * @param EmbeddedForm $entity
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function update(EmbeddedForm $entity = null)
    {
        if (!$entity) {
            $entity = new EmbeddedForm();
        }

        $form = $this->createForm(new EmbeddedFormType(), $entity);
        $form->handleRequest($this->get('request'));
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        if ($form->isValid()) {
            $entity = $form->getData();
            $em->persist($entity);
            $em->flush();


            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.embeddedform.controller.saved_message')
            );

            return $this->get('oro_ui.router')->actionRedirect(
                array(
                    'route' => 'oro_embedded_form_update',
                    'parameters' => array('id' => $entity->getId()),
                ),
                array(
                    'route' => 'oro_embedded_form_view',
                    'parameters' => array('id' => $entity->getId()),
                )
            );

        }

        /** @var EmbeddedFormManager $formManager */
        $formManager = $this->get('oro_embedded_form.manager');
        return array(
            'entity' => $entity,
            'defaultCss' => $formManager->getDefaultCssByType($entity->getFormType()),
            'defaultSuccessMessage' => $formManager->getDefaultSuccessMessageByType($entity->getFormType()),
            'form' => $form->createView()
        );
    }
}
