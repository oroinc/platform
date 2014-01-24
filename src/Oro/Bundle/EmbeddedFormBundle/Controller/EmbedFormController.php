<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;


use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use OroCRM\Bundle\ContactUsBundle\Entity\ContactRequest;
use OroCRM\Bundle\ContactUsBundle\Form\Type\ContactRequestType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class EmbedFormController extends Controller
{
    /**
     * @Route("/submit/{id}", name="oro_embedded_form_submit", requirements={"id"="[-\d\w]+"})
     * @Template
     */
    public function formAction(EmbeddedForm $formEntity, Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $form = $this->get('oro_embedded_form.manager')->createForm($formEntity->getFormType());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity = $form->getData();
            $entity->setChannel($formEntity->getChannel());
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('oro_embedded_form_success', ['id' => $formEntity->getId()]));
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/success/{id}", name="oro_embedded_form_success", requirements={"id"="[-\d\w]+"})
     * @Template
     */
    public function formSuccessAction(EmbeddedForm $formEntity)
    {
        return [
            'entity' => $formEntity
        ];
    }
} 
