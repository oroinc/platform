<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;


use Doctrine\ORM\EntityManager;
use FOS\Rest\Util\Codes;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedFormEntity;
use Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormType;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EmbeddedFormController extends Controller
{
    /**
     * @Route(name="oro_embedded_form_list")
     * @Template()
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("create", name="oro_embedded_form_create")
     * @Template("OroEmbeddedFormBundle:EmbeddedForm:update.html.twig")
     */
    public function createAction()
    {
        return $this->update();
    }

    /**
     * @Route("delete/{id}", name="oro_embedded_form_delete", requirements={"id"="[-\d\w]+"})
     */
    public function deleteAction(EmbeddedFormEntity $entity)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $em->remove($entity);
        $em->flush();

        return new JsonResponse('', Codes::HTTP_OK);
    }

    /**
     * @Route("update/{id}", name="oro_embedded_form_update", requirements={"id"="[-\d\w]+"})
     * @Template()
     */
    public function updateAction(EmbeddedFormEntity $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route("view/{id}", name="oro_embedded_form_view", requirements={"id"="[-\d\w]+"})
     * @Template()
     */
    public function viewAction(EmbeddedFormEntity $entity)
    {
        return [
            'entity' => $entity
        ];
    }

    protected function update(EmbeddedFormEntity $entity = null)
    {
        if (!$entity) {
            $entity = new EmbeddedFormEntity();
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
                $this->get('translator')->trans('oro.embedded_form.controller.embedded_form.saved.message')
            );

            return $this->get('oro_ui.router')->actionRedirect(
                array(
                    'route' => 'oro_embedded_form_update',
                    'parameters' => array('id' => $entity->getId()),
                ),
                array(
                    'route' => 'oro_embedded_form_list'
                )
            );

        }


        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

} 
